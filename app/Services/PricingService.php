<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Holiday;
use App\Models\PricingRule;
use App\Models\VehicleCategory;
use App\Support\BookingPeriod;
use App\Support\PriceQuote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Calcul du prix. Aucun centime, jamais : on reste en entiers FCFA du début à la
 * fin, et le seul arrondi du système est celui des blocs de chauffeur.
 */
class PricingService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  bool  $withDriver  état de la case chauffeur, pour renvoyer le bon total
     * @param  bool  $depositRequired  décision de l'agence pour ce véhicule précis
     */
    public function quote(VehicleCategory $category, BookingPeriod $period, bool $withDriver = false, bool $depositRequired = true): PriceQuote
    {
        $days = max($this->settings->int('booking.min_days'), $period->days());
        $holidays = $this->holidaysIn($period);
        $dailyPrice = $this->resolveDailyPrice($category, $period, $days, $holidays);

        $priceRental = $dailyPrice * $days;

        $driverMode = $this->settings->string('driver.with_car_mode');
        $blockHours = max(1, $this->settings->int('driver.block_hours'));
        $driverDailyPrice = $this->settings->int('driver.block_price');
        $blocks = $withDriver ? $period->totalDriverBlocks($blockHours) : 0;

        if ($withDriver) {
            if ($driverMode === 'package') {
                // Le tarif chauffeur remplace le prix de la voiture
                $priceDriver = $days * $driverDailyPrice;
                $priceRental = 0;
            } else {
                // Mode supplement (défaut) : chauffeur s'ajoute au prix de la voiture
                $priceDriver = $days * $driverDailyPrice;
            }
        } else {
            $priceDriver = 0;
        }

        $deposit = $depositRequired ? $category->deposit() : 0;

        return new PriceQuote(
            days: $days,
            dailyPrice: $dailyPrice,
            priceRental: $priceRental,
            withDriver: $withDriver,
            driverBlocks: $blocks,
            driverBlockHours: $blockHours,
            driverHours: $withDriver ? $period->totalHours() : 0,
            driverDailyPrice: $driverDailyPrice,
            priceDriver: $priceDriver,
            priceTotal: $priceRental + $priceDriver,
            deposit: $deposit,
            amountToCarry: $priceRental + $priceDriver + $deposit,
            lines: $this->buildLines($period, $dailyPrice, $holidays, $category),
            holidayDays: count($holidays),
            appliedRule: null,
        );
    }

    /** Rebuilds the quote from amounts persisted when the booking was priced. */
    public function quoteForBooking(Booking $booking): PriceQuote
    {
        return new PriceQuote(
            days: $booking->days,
            dailyPrice: $booking->daily_price,
            priceRental: $booking->price_rental,
            withDriver: $booking->with_driver,
            driverBlocks: $booking->driver_blocks,
            driverBlockHours: max(1, $this->settings->int('driver.block_hours')),
            driverHours: $booking->driver_hours,
            driverDailyPrice: $booking->driver_daily_price ?? $this->settings->int('driver.block_price'),
            priceDriver: $booking->price_driver,
            priceTotal: $booking->price_total,
            deposit: $booking->deposit_amount,
            amountToCarry: $booking->price_total + $booking->deposit_amount,
            holidayDays: $booking->holiday_days,
            legacyDriverPricing: $booking->with_driver && $booking->driver_daily_price === null,
        );
    }

    /**
     * Tarif journalier retenu pour la période.
     *
     * Ordre : tarif de la catégorie, éventuellement remplacé par la règle de
     * saisonnalité la plus prioritaire qui s'applique à la durée demandée.
     */
    public function resolveDailyPrice(
        VehicleCategory $category,
        BookingPeriod $period,
        ?int $days = null,
        ?array $holidays = null,
    ): int {
        $days ??= $period->days();
        $holidays ??= $this->holidaysIn($period);

        $rule = $this->matchingRule($category, $days, $period);

        // Tous les jours fériés, ou seulement certains : on garde le pire cas.
        if ($holidays !== [] && $category->daily_price_holiday !== null) {
            return (int) $category->daily_price_holiday;
        }

        return (int) ($rule?->daily_price ?? $category->daily_price);
    }

    /** Règle applicable : la plus prioritaire dont la durée tombe dans le créneau. */
    public function matchingRule(VehicleCategory $category, int $days, BookingPeriod $period): ?PricingRule
    {
        return $category->pricingRules()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->first(fn (PricingRule $rule) => $rule->appliesTo($days));
    }

    /** Jours fériés tarifés à l'intérieur de la période. */
    /** @return list<CarbonImmutable> */
    public function holidaysIn(BookingPeriod $period): array
    {
        $days = Holiday::query()
            ->where('is_price_surcharged', true)
            ->between($period->startsAt, $period->endsAt)
            ->pluck('date');

        return $days->map(fn ($date) => Date::parse($date)->startOfDay())->all();
    }

    /**
     * Détail jour par jour, affiché dans le récapitulatif du tunnel.
     *
     * @return list<array{date: string, label: string, price: int, surcharge: int}>
     */
    private function buildLines(BookingPeriod $period, int $dailyPrice, array $holidays, VehicleCategory $category): array
    {
        $holidayDates = collect($holidays)->map(fn ($d) => $d->toDateString())->all();
        $surcharge = (int) ($category->daily_price_holiday ?? 0) - $dailyPrice;

        return array_map(function ($day) use ($dailyPrice, $holidayDates, $surcharge) {
            $isHoliday = in_array($day->toDateString(), $holidayDates, true);

            return [
                'date' => $day->toDateString(),
                'label' => $day->translatedFormat('D j M'),
                'price' => $dailyPrice,
                'surcharge' => $isHoliday ? $surcharge : 0,
            ];
        }, $period->eachDay());
    }
}
