<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\VehicleStatus;
use App\Exceptions\BookingException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\BookingPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Écriture des réservations.
 *
 * Toute création passe par une transaction avec verrou de ligne sur le véhicule :
 * c'est la seule façon d'empêcher deux clients simultanés de réserver la même
 * voiture. Le contrôle de disponibilité est refait *à l'intérieur* du verrou,
 * jamais avant, sinon la vérification est caduque au moment de commiter.
 */
class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
        private readonly ReferenceGenerator $references,
    ) {}

    /**
     * Crée une réservation en figeant les prix.
     *
     * @param  array{
     *     vehicle: Vehicle,
     *     period: BookingPeriod,
     *     user: ?User,
     *     with_driver: bool,
     *     phone_verified_at: ?\DateTimeInterface,
     *     public_token?: string,
     *     customer_name?: string,
     *     customer_phone?: string,
     *     customer_email?: string,
     *     customer_comment?: ?string,
     *     guest_access_token_hash?: string
     * }  $data
     */
    public function create(array $data): Booking
    {
        $vehicle = $data['vehicle'];
        $period = $data['period'];
        $withDriver = $data['with_driver'];
        $user = $data['user'] ?? null;

        return DB::transaction(function () use ($vehicle, $period, $withDriver, $user, $data) {
            // Verrou de ligne : deux réservations simultanées sur le même véhicule
            // se sérialisent ici. Sans ce verrou, le second commit overwrite le premier.
            $locked = Vehicle::query()->whereKey($vehicle->getKey())->lockForUpdate()->firstOrFail();

            $this->assertVehicleBookable($locked, $period);
            $this->assertDriverAvailable($period, $withDriver);
            $this->assertCustomerEligible($user, $period);
            $quote = $this->pricing->quote($locked->category, $period, $withDriver);

            $booking = new Booking([
                'reference' => $this->references->generate(),
                'user_id' => $user?->id,
                'vehicle_id' => $locked->id,
                'category_id' => $locked->category_id,
                'starts_at' => $period->startsAt,
                'ends_at' => $period->endsAt,
                'days' => $quote->days,
                'pickup_hour' => $period->startsAtHour(),
                'return_hour' => $period->endsAtHour(),
                'with_driver' => $withDriver,
                'driver_blocks' => $quote->driverBlocks,
                'driver_hours' => $quote->driverHours,
                ...$quote->toAmounts(),
                'currency' => 'XOF',
                'status' => BookingStatus::EnAttente,
                'document_verification_status' => DocumentVerificationStatus::EnAttente,
                'identity_original_verified' => false,
                'identity_name_matches' => false,
                'customer_age_eligible' => false,
                'driver_license_original_verified' => false,
                'driver_license_category_authorized' => false,
                'phone_verified_at' => $data['phone_verified_at'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_comment' => $data['customer_comment'] ?? null,
                'guest_access_token_hash' => $data['guest_access_token_hash'] ?? null,
                'grace_expires_at' => $period->startsAt
                    ->copy()
                    ->addHours($this->settings->int('booking.grace_period_hours')),
                'public_token' => $data['public_token'] ?? bin2hex(random_bytes(20)),
            ]);

            $booking->save();

            $this->log('booking.created', $booking, [
                'vehicle' => $locked->displayName(),
                'periode' => $period->startsAtDate().' → '.$period->endsAtDate(),
                'total' => $quote->priceTotal,
            ]);

            return $booking->load(['vehicle.category', 'user']);
        });
    }

    /**
     * Recalcule et réécrit les montants après une modification de dates.
     * La réservation garde sa référence et son historique.
     */
    public function reprice(Booking $booking, BookingPeriod $period, bool $withDriver): Booking
    {
        return DB::transaction(function () use ($booking, $period, $withDriver) {
            $previousWithDriver = $booking->with_driver;
            $verificationMustBeRepeated = $previousWithDriver !== $withDriver
                || ! $booking->starts_at->equalTo($period->startsAt)
                || ! $booking->ends_at->equalTo($period->endsAt);
            Vehicle::query()->whereKey($booking->vehicle_id)->lockForUpdate()->firstOrFail();

            $this->assertVehicleBookable($booking->vehicle, $period, $booking);
            $this->assertDriverAvailable($period, $withDriver);

            $quote = $this->pricing->quote($booking->category, $period, $withDriver);

            $booking->update([
                'starts_at' => $period->startsAt,
                'ends_at' => $period->endsAt,
                'days' => $quote->days,
                'pickup_hour' => $period->startsAtHour(),
                'return_hour' => $period->endsAtHour(),
                'with_driver' => $withDriver,
                'document_verification_status' => $verificationMustBeRepeated
                    ? DocumentVerificationStatus::EnAttente
                    : $booking->document_verification_status,
                'identity_original_verified' => $verificationMustBeRepeated
                    ? false
                    : $booking->identity_original_verified,
                'identity_name_matches' => $verificationMustBeRepeated
                    ? false
                    : $booking->identity_name_matches,
                'customer_age_eligible' => $verificationMustBeRepeated
                    ? false
                    : $booking->customer_age_eligible,
                'driver_license_original_verified' => $verificationMustBeRepeated
                    ? false
                    : $booking->driver_license_original_verified,
                'driver_license_category_authorized' => $verificationMustBeRepeated
                    ? false
                    : $booking->driver_license_category_authorized,
                'documents_verified_at' => $verificationMustBeRepeated
                    ? null
                    : $booking->documents_verified_at,
                'documents_verified_by' => $verificationMustBeRepeated
                    ? null
                    : $booking->documents_verified_by,
                'document_verification_note' => $verificationMustBeRepeated
                    ? null
                    : $booking->document_verification_note,
                'driver_blocks' => $quote->driverBlocks,
                'driver_hours' => $quote->driverHours,
                ...$quote->toAmounts(),
                'grace_expires_at' => $period->startsAt
                    ->copy()
                    ->addHours($this->settings->int('booking.grace_period_hours')),
            ]);

            $this->log('booking.repriced', $booking, ['total' => $quote->priceTotal]);

            return $booking->refresh();
        });
    }

    public function confirm(Booking $booking, User $agent): Booking
    {
        return DB::transaction(function () use ($booking, $agent) {
            $vehicle = Vehicle::query()
                ->whereKey($booking->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedBooking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $period = BookingPeriod::fromDates(
                $lockedBooking->starts_at->toImmutable(),
                $lockedBooking->ends_at->toImmutable(),
                $this->settings->openHour(),
                $this->settings->closeHour(),
            );

            $this->assertTransition($lockedBooking, BookingStatus::Confirmee);
            $this->assertVehicleBookable($vehicle, $period, $lockedBooking);
            $this->assertDriverAvailable($period, $lockedBooking->with_driver);

            $lockedBooking->update([
                'status' => BookingStatus::Confirmee,
                'confirmed_at' => now(),
                'status_reason' => null,
            ]);

            $this->log('booking.confirmed', $lockedBooking, [], $agent);

            return $lockedBooking->refresh();
        });
    }

    public function refuse(Booking $booking, User $agent, string $reason): Booking
    {
        $this->assertTransition($booking, BookingStatus::Refusee);

        $booking->update([
            'status' => BookingStatus::Refusee,
            'status_reason' => $reason,
        ]);

        $this->log('booking.refused', $booking, ['motif' => $reason], $agent);

        return $booking->refresh();
    }

    public function cancel(Booking $booking, User $actor, string $by = 'client'): Booking
    {
        if (! $booking->status->canBeCancelled()) {
            throw new BookingException(
                __('booking.error.cannot_cancel', ['status' => $booking->status->label()]),
            );
        }

        $booking->update([
            'status' => BookingStatus::Annulee,
            'cancelled_at' => now(),
            'cancelled_by' => $by,
        ]);

        $this->log('booking.cancelled', $booking, ['par' => $by], $actor);

        return $booking->refresh();
    }

    /** Retrait effectif : la voiture part, la caution part au même moment. */
    public function start(Booking $booking, User $agent, ?int $mileage = null, ?string $fuelLevel = null): Booking
    {
        return DB::transaction(function () use ($booking, $agent, $mileage, $fuelLevel) {
            $vehicle = Vehicle::query()
                ->whereKey($booking->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedBooking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransition($lockedBooking, BookingStatus::EnCours);

            if (! $lockedBooking->isReadyForHandover()) {
                throw new BookingException(__('booking.error.documents_not_verified'));
            }

            $lockedBooking->update([
                'status' => BookingStatus::EnCours,
                'started_at' => now(),
                'mileage_at_pickup' => $mileage ?? $vehicle->mileage,
                'fuel_level_pickup' => $fuelLevel ?? $vehicle->fuel_level,
            ]);

            $vehicle->update([
                'status' => VehicleStatus::EnLocation,
                'mileage' => $mileage ?? $vehicle->mileage,
            ]);

            $this->log('booking.started', $lockedBooking, [], $agent);

            return $lockedBooking->refresh();
        });
    }

    public function finish(Booking $booking, User $agent, ?int $mileage = null, ?string $fuelLevel = null): Booking
    {
        $this->assertTransition($booking, BookingStatus::Terminee);

        $booking->update([
            'status' => BookingStatus::Terminee,
            'ended_at' => now(),
            'mileage_at_return' => $mileage ?? $booking->mileage_at_pickup,
            'fuel_level_return' => $fuelLevel ?? $booking->fuel_level_pickup,
        ]);

        $booking->vehicle?->update([
            'status' => VehicleStatus::Disponible,
            'fuel_level' => $fuelLevel ?? $booking->fuel_level_pickup,
        ]);

        $this->log('booking.finished', $booking, [], $agent);

        return $booking->refresh();
    }

    /**
     * Absence constatée : le véhicule redevient disponible et le compteur du
     * client est incrémenté. Au-delà du seuil, le compte est bloqué.
     */
    public function markAsNoShow(Booking $booking): Booking
    {
        if ($booking->status === BookingStatus::NonPresente) {
            return $booking;
        }

        $booking->update([
            'status' => BookingStatus::NonPresente,
            'status_reason' => __('booking.status_reason.no_show'),
        ]);

        $booking->vehicle?->update(['status' => VehicleStatus::Disponible]);

        $user = $booking->user;

        if ($user !== null) {
            $absences = $user->no_show_count + 1;
            $maxAbsences = $this->settings->int('no_show.max_absences');

            $user->update([
                'no_show_count' => $absences,
                'blocked_at' => $absences >= $maxAbsences ? now() : $user->blocked_at,
                'block_reason' => $absences >= $maxAbsences
                    ? __('user.blocked_too_many_no_shows', ['max' => $maxAbsences])
                    : $user->block_reason,
            ]);
        }

        $this->log('booking.no_show', $booking, ['absences' => $booking->user?->no_show_count]);

        return $booking->refresh();
    }

    public function markReminderSent(Booking $booking): Booking
    {
        $booking->update(['reminder_sent_at' => now()]);

        return $booking;
    }

    public function markPresenceConfirmed(Booking $booking): Booking
    {
        $booking->update(['client_confirmed_presence_at' => now()]);

        return $booking;
    }

    public function markPhoneVerified(Booking $booking, ?\DateTimeInterface $at = null): Booking
    {
        $booking->update(['phone_verified_at' => $at ?? now()]);

        return $booking;
    }

    // ── Garde-fous ────────────────────────────────────────────────────────────

    /**
     * Un véhicule est-il réellement réservable sur ce créneau ?
     *
     * @param  Booking|null  $ignore  réservation à ne pas compter comme conflit —
     *                                sans cela, re-dater une réservation la ferait
     *                                entrer en conflit avec elle-même.
     */
    private function assertVehicleBookable(Vehicle $vehicle, BookingPeriod $period, ?Booking $ignore = null): void
    {
        if (! $vehicle->isBookable()) {
            throw new BookingException(__('booking.error.vehicle_not_bookable'));
        }

        $padded = $period->padded($this->settings->int('booking.turnaround_hours'));

        $conflict = Booking::query()
            ->active()
            ->where('vehicle_id', $vehicle->id)
            ->forPeriod($padded->startsAt, $padded->endsAt)
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($conflict) {
            throw new BookingException(__('booking.error.vehicle_just_taken'));
        }
    }

    private function assertDriverAvailable(BookingPeriod $period, bool $withDriver): void
    {
        if ($withDriver) {
            $this->availability->lockAvailableDrivers();

            if (! $this->availability->canOfferDriver($period)) {
                throw new BookingException(__('booking.error.no_driver_available'));
            }
        }
    }

    private function assertCustomerEligible(?User $user, BookingPeriod $period): void
    {
        if ($user === null) {
            return;
        }

        if ($user->isBlocked() && ! $user->prepayment_required) {
            throw new BookingException(__('booking.error.account_blocked'));
        }

        $max = $this->settings->int('booking.max_active_bookings');

        if ($user->activeBookingsCount() >= $max) {
            throw new BookingException(__('booking.error.too_many_bookings', ['max' => $max]));
        }

        $minAge = $this->settings->int('booking.min_age');

        if ($user->age() !== null && $user->age() < $minAge) {
            throw new BookingException(__('booking.error.too_young', ['age' => $minAge]));
        }
    }

    private function assertTransition(Booking $booking, BookingStatus $target): void
    {
        $allowed = match ($target) {
            BookingStatus::Confirmee => BookingStatus::EnAttente,
            BookingStatus::EnCours => BookingStatus::Confirmee,
            BookingStatus::Terminee => BookingStatus::EnCours,
            BookingStatus::Refusee => BookingStatus::EnAttente,
            default => null,
        };

        if ($allowed !== null && $booking->status !== $allowed) {
            throw new BookingException(__('booking.error.invalid_transition', [
                'from' => $booking->status->label(),
                'to' => $target->label(),
            ]));
        }
    }

    private function log(string $event, Booking $booking, array $description = [], ?User $actor = null): void
    {
        AuditLog::query()->create([
            'user_id' => $actor?->id ?? auth()->id(),
            'event' => $event,
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->getKey(),
            'description' => json_encode($description, JSON_UNESCAPED_UNICODE),
            'ip_address' => request()->ip(),
        ]);
    }
}
