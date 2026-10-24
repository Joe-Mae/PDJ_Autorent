<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\DriverOnlyBookingStatus;
use App\Exceptions\BookingException;
use App\Models\DriverOnlyBooking;
use App\Support\BookingPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DriverOnlyBookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SettingsService $settings,
        private readonly DocumentStorageService $storage,
    ) {}

    /**
     * Une demande de chauffeur seul est une réservation : sans pièce
     * d'identité, elle n'est jamais enregistrée.
     *
     * @param  array{name: string, phone: string, du: string, au: string}  $data
     */
    public function create(array $data, UploadedFile $identityDocument): DriverOnlyBooking
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

        return DB::transaction(function () use ($data, $period, $days, $dailyPrice, $identityDocument) {
            $this->availability->lockAvailableDrivers();

            if (! $this->availability->canOfferDriver($period)) {
                throw new BookingException(__('driver.none_available'));
            }

            $booking = DriverOnlyBooking::query()->create([
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

            $stored = $this->storage->store(
                $identityDocument,
                'driver-only/'.$booking->reference,
                'identity_document',
            );

            $booking->documents()->create([
                'user_id' => null,
                'type' => DocumentType::IdentityCard,
                'status' => DocumentStatus::EnAttente,
                'disk' => $stored->disk,
                'path' => $stored->path,
                'original_filename' => $stored->originalFilename,
                'mime_type' => $stored->mimeType,
                'size_kb' => $stored->sizeKb,
            ]);

            return $booking;
        });
    }
}
