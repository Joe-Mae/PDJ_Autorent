<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\DocumentVerificationStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Une réservation d'une semaine, à partir de demain, 8 h → 19 h. Les
     * montants correspondent au barème de la catégorie de référence : ils sont
     * recalculés par PricingService quand le test exercise la création, et
     * posés tels quels quand le test part d'une réservation déjà existante.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->startOfDay()->setTime(8, 0);
        $endsAt = $startsAt->copy()->addDays(4)->setTime(19, 0);

        return [
            'reference' => 'PDJ-'.fake()->unique()->regexify('[34679ACDEFHJKMNPQRTUVWXY]{6}'),
            'user_id' => null,
            'vehicle_id' => Vehicle::factory(),
            'category_id' => fn (array $attributes) => Vehicle::find($attributes['vehicle_id'])?->category_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'days' => 4,
            'pickup_hour' => 8,
            'return_hour' => 19,
            'with_driver' => false,
            'driver_blocks' => 0,
            'driver_hours' => 0,
            'daily_price' => 18000,
            'price_rental' => 72000,
            'price_driver' => 0,
            'price_total' => 72000,
            'deposit_amount' => 60000,
            'currency' => 'XOF',
            'holiday_days' => 0,
            'status' => BookingStatus::EnAttente,
            'document_verification_status' => DocumentVerificationStatus::EnAttente,
            'public_token' => bin2hex(random_bytes(20)),
        ];
    }

    /** Réservation d'un véhicule et d'une catégorie donnés. */
    public function on(Vehicle $vehicle): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_id' => $vehicle->id,
            'category_id' => $vehicle->category_id,
        ]);
    }

    public function onCategory(VehicleCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmee,
            'confirmed_at' => now(),
        ]);
    }

    /** Location démarrée : le véhicule est parti, la caution est encaissée. */
    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::EnCours,
            'confirmed_at' => now()->subDay(),
            'started_at' => now(),
        ]);
    }

    /** Réservation terminée, caution rendue. */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Terminee,
            'confirmed_at' => now()->subWeek(),
            'started_at' => now()->subDays(6),
            'ended_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Annulee,
            'cancelled_at' => now(),
            'cancelled_by' => 'client',
        ]);
    }

    public function withDriver(int $blocks = 12, int $priceDriver = 60000): static
    {
        return $this->state(fn (array $attributes) => [
            'with_driver' => true,
            'document_verification_status' => DocumentVerificationStatus::EnAttente,
            'driver_blocks' => $blocks,
            'driver_hours' => $blocks * 4,
            'price_driver' => $priceDriver,
            'price_total' => $attributes['price_rental'] + $priceDriver,
        ]);
    }
}
