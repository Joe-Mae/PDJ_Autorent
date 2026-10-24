<?php

namespace Database\Factories;

use App\Models\DriverOnlyBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverOnlyBooking>
 */
class DriverOnlyBookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = 1;
        $dailyPrice = 15000;

        return [
            'reference' => fake()->unique()->bothify('SOLO######'),
            'customer_name' => fake()->name(),
            'phone' => '+228 90 12 34 56',
            'starts_at' => now()->addDays(2)->setTime(8, 0),
            'ends_at' => now()->addDays(3)->setTime(19, 0),
            'days' => $days,
            'daily_price' => $dailyPrice,
            'price_total' => $days * $dailyPrice,
            'status' => 'en_attente',
            'driver_id' => null,
        ];
    }
}
