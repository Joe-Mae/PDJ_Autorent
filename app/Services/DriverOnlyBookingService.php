<?php

namespace App\Services;

use App\Enums\DriverOnlyBookingStatus;
use App\Exceptions\BookingException;
use App\Models\DriverOnlyBooking;
use App\Support\BookingPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DriverOnlyBookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{name: string, phone: string, du: string, au: string}  $data
     */
    public function create(array $data): DriverOnlyBooking
    {
        $period = BookingPeriod::make(
            $data['du'],
            $data['au'],
            $this->settings->openHour(),
            $this->settings->closeHour(),
            $this->settings->openHour(),
            $this->settings->closeHour(),
        );
        $days = max(1, $period->days());
        $discountAfterDays = $this->settings->int('driver.solo_discount_after_days');
        $dailyPrice = $days > $discountAfterDays
            ? $this->settings->int('driver.solo_discount_daily_price')
            : $this->settings->int('driver.solo_daily_price');

        return DB::transaction(function () use ($data, $period, $days, $dailyPrice) {
            $this->availability->lockAvailableDrivers();

            if (! $this->availability->canOfferDriver($period)) {
                throw new BookingException(__('driver.none_available'));
            }

            return DriverOnlyBooking::query()->create([
                'reference' => Str::upper(Str::random(12)),
                'customer_name' => $data['name'],
                'phone' => $data['phone'],
                'starts_at' => $period->startsAt,
                'ends_at' => $period->endsAt,
                'days' => $days,
                'daily_price' => $dailyPrice,
                'price_total' => $days * $dailyPrice,
                'status' => DriverOnlyBookingStatus::EnAttente,
            ]);
        });
    }
}
