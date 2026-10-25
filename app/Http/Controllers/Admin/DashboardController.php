<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverOnlyBookingStatus;
use App\Enums\PaymentKind;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomerDocument;
use App\Models\Driver;
use App\Models\DriverOnlyBooking;
use App\Models\Payment;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord de l'agence.
 *
 * On commence par ce qui demande une action aujourd'hui, pas par un graphique :
 * les documents à vérifier avant ce soir, les véhicules qui partent, les
 * retards. Les chiffres de la période arrivent ensuite, pour l'agent qui fait
 * le point le vendredi.
 *
 * Tous les montants viennent des paiements enregistrés au guichet : une
 * réservation confirmée n'est pas un revenu tant qu'elle n'a pas été encaissée.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = in_array($request->query('periode'), ['semaine', 'mois', 'annee'], true)
            ? $request->query('periode')
            : 'mois';

        [$from, $to] = $this->periodRange($period);

        [$chartFrom, $chartTo] = [now()->subWeeks(11)->startOfWeek(), now()->endOfWeek()];

        return view('admin.dashboard', [
            'period' => $period,
            'range' => ['from' => $from, 'to' => $to],

            'stats' => [
                'total_vehicles' => Vehicle::query()->count(),
                'available' => Vehicle::query()
                    ->where('is_active', true)
                    ->whereIn('status', [VehicleStatus::Disponible->value, VehicleStatus::Reserve->value])
                    ->whereDoesntHave('bookings', fn ($query) => $query
                        ->active()
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>', now()))
                    ->count(),
                'en_location' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->distinct('vehicle_id')
                    ->count('vehicle_id'),
                'unavailable' => Vehicle::query()
                    ->where(function ($query) {
                        $query->whereIn('status', [VehicleStatus::EnMaintenance->value, VehicleStatus::HorsService->value])
                            ->orWhere('is_active', false);
                    })
                    ->count(),
                'total_bookings' => Booking::query()->count(),
                'pending' => Booking::query()->where('status', BookingStatus::EnAttente->value)->count(),
                'confirmed' => Booking::query()->where('status', BookingStatus::Confirmee->value)->count(),
                'in_progress' => Booking::query()->where('status', BookingStatus::EnCours->value)->count(),
                'overdue' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->where('ends_at', '<', now())
                    ->count(),
                'completed' => Booking::query()->where('status', BookingStatus::Terminee->value)->count(),
                'chauffeurs' => Driver::query()->available()->count(),
            ],

            // Un graphique sans chiffre lisible n'est qu'une décoration : la
            // série porte ses valeurs, affichées au-dessus de chaque barre.
            'bookingSeries' => $this->bookingSeries($chartFrom, $chartTo),

            'serviceBreakdown' => $this->serviceBreakdown($from, $to),

            'periodRevenue' => Payment::query()
                ->where('kind', PaymentKind::Rental->value)
                ->whereBetween('recorded_at', [$from, $to])
                ->sum('amount'),

            // Reste à encaisser : uniquement les réservations qui immobilisent
            // encore un véhicule. Une réservation annulée ne doit pas gonfler
            // l'impayé.
            'outstanding' => Booking::query()
                ->whereIn('status', [
                    BookingStatus::EnAttente->value,
                    BookingStatus::Confirmee->value,
                    BookingStatus::EnCours->value,
                ])
                ->withSum(
                    ['payments as rental_paid' => fn ($query) => $query->where('kind', PaymentKind::Rental->value)],
                    'amount'
                )
                ->get(['id', 'price_total'])
                ->sum(fn (Booking $booking): int => max(0, (int) $booking->price_total - (int) $booking->rental_paid)),

            'today' => [
                'departures' => Booking::query()
                    ->whereIn('status', [BookingStatus::Confirmee->value, BookingStatus::EnCours->value])
                    ->whereDate('starts_at', today())
                    ->with(['vehicle', 'user'])
                    ->orderBy('pickup_hour')
                    ->get(),

                'returns' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->whereDate('ends_at', today())
                    ->with(['vehicle', 'user'])
                    ->get(),

                'overdue' => Booking::query()
                    ->where('status', BookingStatus::EnCours->value)
                    ->where('ends_at', '<', now())
                    ->with(['vehicle', 'user'])
                    ->get(),
            ],

            'recentBookings' => Booking::query()
                ->with(['vehicle', 'user'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),

            'upcomingReturns' => Booking::query()
                ->where('status', BookingStatus::EnCours->value)
                ->where('ends_at', '<=', now()->addDays(7))
                ->with(['vehicle', 'user'])
                ->orderBy('ends_at')
                ->limit(10)
                ->get(),

            // Les trois prestations vivent dans deux tables : les locations
            // (avec ou sans chauffeur) et les demandes de chauffeur seul. Le
            // tableau de bord les remet sur une seule ligne du temps, sinon
            // une demande de chauffeur seul n'apparaît nulle part.
            'upcoming' => $this->upcomingPrestations(),

            'pending' => [
                'validation' => Booking::query()
                    ->where('status', BookingStatus::EnAttente->value)
                    ->with(['vehicle.category', 'user', 'documents'])
                    ->orderBy('starts_at')
                    ->get(),

                // La file de documents est triée par urgence : ce qui part demain
                // passe avant ce qui part dans trois semaines.
                'documents' => CustomerDocument::query()
                    ->pending()
                    ->with(['user', 'booking.vehicle'])
                    ->orderByRaw('COALESCE((SELECT starts_at FROM bookings WHERE bookings.id = documents.booking_id), created_at) ASC')
                    ->limit(20)
                    ->get(),
            ],

            'cash' => [
                'today_in' => Payment::query()
                    ->whereNotIn('kind', ['caution_restitution'])
                    ->whereDate('recorded_at', today())
                    ->sum('amount'),

                'today_out' => Payment::query()
                    ->where('kind', 'caution_restitution')
                    ->whereDate('recorded_at', today())
                    ->sum('amount'),

                'deposits_held' => DB::table('payments')
                    ->where('kind', 'caution')
                    ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                    ->whereIn('bookings.status', [
                        BookingStatus::EnCours->value,
                        BookingStatus::Confirmee->value,
                        BookingStatus::EnAttente->value,
                    ])
                    ->sum('amount'),
            ],
        ]);
    }

    /**
     * Série des réservations par semaine sur les douze dernières semaines.
     *
     * Le regroupement se fait en PHP : la base n'a pas à connaître la notion de
     * semaine de l'agence, et la requête reste portable (MySQL en production,
     * SQLite dans les tests).
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function bookingSeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = Booking::query()
            ->whereBetween('starts_at', [$from, $to])
            ->get(['starts_at'])
            ->countBy(fn (Booking $booking): string => $booking->starts_at->copy()->startOfWeek()->toDateString());

        return collect(range(0, 11))
            ->map(function (int $offset) use ($from, $counts): array {
                // `copy()` est indispensable : Carbon (mutable) avancerait la
                // date de départ à chaque tour, et les étiquettes se
                // décaleraient de semaine en semaine.
                $week = $from->copy()->addWeeks($offset);

                return [
                    'label' => $week->translatedFormat('d/m'),
                    'count' => (int) ($counts[$week->toDateString()] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Répartition des trois prestations sur la période choisie.
     *
     * @return array<string, int>
     */
    private function serviceBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'car_only' => Booking::query()
                ->where('with_driver', false)
                ->whereBetween('starts_at', [$from, $to])
                ->count(),
            'with_driver' => Booking::query()
                ->where('with_driver', true)
                ->whereBetween('starts_at', [$from, $to])
                ->count(),
            'driver_only' => DriverOnlyBooking::query()
                ->whereBetween('starts_at', [$from, $to])
                ->count(),
        ];
    }

    /**
     * Les prochaines prestations, tous types confondus, triées par départ.
     *
     * @return array<int, array{at: CarbonInterface, reference: string, client: string, resource: string, type: string, url: string}>
     */
    private function upcomingPrestations(): array
    {
        $from = now()->startOfDay();
        $to = now()->addDays(7)->endOfDay();

        $cars = Booking::query()
            ->whereIn('status', [BookingStatus::Confirmee->value, BookingStatus::EnCours->value])
            ->whereBetween('starts_at', [$from, $to])
            ->with(['vehicle', 'user', 'driver'])
            ->get()
            ->map(fn (Booking $booking): array => [
                'at' => $booking->starts_at,
                'reference' => $booking->reference,
                'client' => $booking->user?->displayName() ?? $booking->customer_name ?? '—',
                'resource' => $booking->vehicle->displayName()
                    .($booking->with_driver && $booking->driver ? ' · '.$booking->driver->displayName() : ''),
                'type' => $booking->with_driver ? 'with_driver' : 'car_only',
                'url' => route('admin.bookings.show', $booking),
            ]);

        $solo = DriverOnlyBooking::query()
            ->where('status', DriverOnlyBookingStatus::Confirmee->value)
            ->whereBetween('starts_at', [$from, $to])
            ->with('driver')
            ->get()
            ->map(fn (DriverOnlyBooking $booking): array => [
                'at' => $booking->starts_at,
                'reference' => $booking->reference,
                'client' => $booking->customer_name,
                'resource' => $booking->driver?->displayName() ?? __('admin.no_driver'),
                'type' => 'driver_only',
                'url' => route('admin.driver-only-bookings.index'),
            ]);

        return $cars->merge($solo)
            ->sortBy(fn (array $item) => $item['at'])
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodRange(string $period): array
    {
        $today = now()->toImmutable();

        return match ($period) {
            'semaine' => [$today->startOfWeek(), $today->endOfWeek()],
            'annee' => [$today->startOfYear(), $today->endOfYear()],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };
    }
}
