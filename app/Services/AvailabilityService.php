<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\DriverOnlyBooking;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Disponibilité réelle des véhicules et des chauffeurs.
 *
 * Règle unique, appliquée partout : un créneau est pris si
 *     debut_demande < fin_existante ET fin_demandee > debut_existant
 *
 * Le créneau demandé est élargi du battement (2 h) avant confrontation, ce qui
 * garantit qu'un client ne peut pas prendre une voiture juste après le retour
 * du précédent sans que l'agent ait le temps de la remettre en état.
 */
class AvailabilityService
{
    public function __construct(private readonly SettingsService $settings) {}

    /** Identifiants des véhicules libres sur la période, battement compris. */
    public function freeVehicleIds(BookingPeriod $period): array
    {
        $padded = $period->padded($this->settings->int('booking.turnaround_hours'));

        $taken = Booking::query()
            ->active()
            ->forPeriod($padded->startsAt, $padded->endsAt)
            ->distinct()
            ->pluck('vehicle_id');

        return Vehicle::query()
            ->bookable()
            ->whereNotIn('id', $taken)
            ->pluck('id')
            ->all();
    }

    public function isVehicleFree(Vehicle $vehicle, BookingPeriod $period): bool
    {
        return in_array($vehicle->id, $this->freeVehicleIds($period), true);
    }

    /**
     * Nombre de places chauffeur encore vendables sur la période.
     *
     * On retranche le nombre de réservations avec chauffeur déjà prises du nombre
     * de chauffeurs actifs : le chauffeur est une ressource, pas une option
     * décorative qu'on peut vendre à l'infini.
     */
    public function freeDriverSlots(BookingPeriod $period, ?int $exceptSoloBookingId = null): int
    {
        $drivers = Driver::query()->available()->count();

        if ($drivers === 0) {
            return 0;
        }

        $alreadyBooked = Booking::query()
            ->active()
            ->where('with_driver', true)
            ->forPeriod($period->startsAt, $period->endsAt)
            ->distinct()
            ->count('vehicle_id');

        $soloBookings = DriverOnlyBooking::query()
            ->active()
            ->forPeriod($period->startsAt, $period->endsAt)
            ->when($exceptSoloBookingId, fn (Builder $query, int $id) => $query->where('id', '!=', $id))
            ->count();

        return max(0, $drivers - $alreadyBooked - $soloBookings);
    }

    /** Serialize chauffeur bookings against other requests in the same period. */
    public function lockAvailableDrivers(): void
    {
        Driver::query()->available()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    /** @return Collection<int, Driver> */
    public function freeDriverRecords(BookingPeriod $period, ?int $exceptSoloBookingId = null): Collection
    {
        $bookedDriverIds = Booking::query()
            ->active()
            ->where('with_driver', true)
            ->whereNotNull('driver_id')
            ->forPeriod($period->startsAt, $period->endsAt)
            ->pluck('driver_id');

        $soloBookedDriverIds = DriverOnlyBooking::query()
            ->active()
            ->whereNotNull('driver_id')
            ->forPeriod($period->startsAt, $period->endsAt)
            ->when($exceptSoloBookingId, fn (Builder $query, int $id) => $query->where('id', '!=', $id))
            ->pluck('driver_id');

        return Driver::query()
            ->available()
            ->whereNotIn('id', $bookedDriverIds->merge($soloBookedDriverIds)->unique())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function canOfferDriver(BookingPeriod $period): bool
    {
        return $this->freeDriverSlots($period) > 0;
    }

    /**
     * Prochaine période libre d'un véhicule, en décalant la date de départ jour
     * par jour. Sert à la page « aucune voiture libre » pour ne pas laisser le
     * client sur une page morte.
     */
    public function nextFreePeriod(Vehicle $vehicle, BookingPeriod $period, int $searchDays = 30): ?BookingPeriod
    {
        $candidate = new BookingPeriod(
            $period->startsAt,
            $period->endsAt,
            $period->openHour,
            $period->closeHour,
        );

        for ($day = 1; $day <= $searchDays; $day++) {
            $candidate = new BookingPeriod(
                $period->startsAt->addDays($day),
                $period->endsAt->addDays($day),
                $period->openHour,
                $period->closeHour,
            );

            if ($this->isVehicleFree($vehicle, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Filtres de recherche, appliqués avant la coupe de disponibilité pour
     * que MySQL travaille sur le plus petit jeu possible.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['categorie'] ?? null, fn (Builder $q, $slug) => $q->whereHas(
                'category',
                fn (Builder $c) => $c->where('slug', $slug),
            ))
            ->when($filters['transmission'] ?? null, fn (Builder $q, $v) => $q->where('transmission', $v))
            ->when($filters['fuel'] ?? null, fn (Builder $q, $v) => $q->whereIn('fuel', (array) $v))
            ->when($filters['seats_min'] ?? null, fn (Builder $q, $v) => $q->where('seats', '>=', (int) $v))
            ->when($filters['bags_min'] ?? null, fn (Builder $q, $v) => $q->where('bags', '>=', (int) $v))
            ->when($filters['with_driver'] ?? false, fn (Builder $q) => $q->whereHas(
                'category',
                fn (Builder $c) => $c->where('daily_price', '>', 0),
            ));
    }

    /** @return Collection<int, Vehicle> */
    public function searchVehicles(array $filters, BookingPeriod $period): Collection
    {
        $freeIds = $this->freeVehicleIds($period);

        if ($freeIds === []) {
            return collect();
        }

        return Vehicle::query()
            ->bookable()
            ->whereIn('id', $freeIds)
            ->with(['category', 'media'])
            ->tap(fn (Builder $q) => $this->applyFilters($q, $filters))
            ->when(
                $filters['price_max'] ?? null,
                fn (Builder $q, $max) => $q->whereHas(
                    'category',
                    fn (Builder $c) => $c->where('daily_price', '<=', (int) $max),
                ),
            )
            ->when(
                $filters['price_min'] ?? null,
                fn (Builder $q, $min) => $q->whereHas(
                    'category',
                    fn (Builder $c) => $c->where('daily_price', '>=', (int) $min),
                ),
            )
            ->orderBy(
                VehicleCategory::query()
                    ->select('daily_price')
                    ->whereColumn('vehicle_categories.id', 'vehicles.category_id'),
                ($filters['sort'] ?? null) === 'prix-desc' ? 'desc' : 'asc',
            )
            ->get();
    }

    /**bornes du curseur de prix, à partir du catalogue réel plutôt que d'une constante. */
    public function priceBounds(): array
    {
        $prices = Vehicle::query()
            ->bookable()
            ->join('vehicle_categories', 'vehicles.category_id', '=', 'vehicle_categories.id')
            ->pluck('vehicle_categories.daily_price');

        return [
            'min' => (int) ($prices->min() ?? 0),
            'max' => (int) ($prices->max() ?? 0),
        ];
    }

    /** Vérifie que l'heure demandée tombe dans la plage d'ouverture. */
    public function isWithinOpeningHours(int $pickupHour, int $returnHour): bool
    {
        $open = $this->settings->openHour();
        $close = $this->settings->closeHour();

        return $pickupHour >= $open && $returnHour <= $close && $returnHour > $pickupHour;
    }

    /** Rappel J-1 : la fenêtre de message se cale sur le créneau demandé. */
    public function reminderWindow(BookingPeriod $period): array
    {
        return [
            'from' => Date::parse($period->startsAtDate())->subDay()->setTime(10, 0),
            'to' => Date::parse($period->startsAtDate())->subDay()->setTime(18, 0),
        ];
    }
}
