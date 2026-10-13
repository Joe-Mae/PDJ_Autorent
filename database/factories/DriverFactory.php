<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => $this->uniquePhone(),
            'whatsapp' => $this->uniquePhone(),
            'photo_path' => null,
            'bio' => 'Chauffeur de l\'agence, connaît Lomé et ses contours.',
            'status' => DriverStatus::Actif,
            'rating' => 5,
            'completed_rentals' => fake()->numberBetween(5, 200),
        ];
    }

    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverStatus::Conge,
        ]);
    }

    /** E.164 sans le « + », même format que les clients : 22896123499. */
    protected function uniquePhone(): string
    {
        static $sequence = 0;

        $sequence++;

        return sprintf('22896%08d', $sequence);
    }
}
