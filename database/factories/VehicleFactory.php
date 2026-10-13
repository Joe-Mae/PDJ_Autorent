<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => VehicleCategory::factory(),
            'brand' => fake()->randomElement(['Toyota', 'Renault', 'Nissan', 'Peugeot', 'Kia']),
            'model' => fake()->randomElement(['Vitz', 'Clio', 'Note', '208', 'Picanto']),
            'plate' => $this->uniquePlate(),
            'year' => fake()->numberBetween(2019, 2025),
            'transmission' => 'manuelle',
            'fuel' => 'essence',
            'seats' => 5,
            'doors' => 5,
            'bags' => 3,
            'air_conditioning' => 1,
            'features' => json_encode(['gps', 'bluetooth']),
            'description_fr' => 'Véhicule de l\'agence, entretenu et vérifié.',
            'description_en' => 'Agency vehicle, serviced and checked.',
            'mileage' => fake()->numberBetween(5_000, 180_000),
            'technical_control_at' => now()->subMonths(4)->toDateString(),
            'next_technical_control_at' => now()->addMonths(8)->toDateString(),
            'fuel_level' => 'full',
            'status' => VehicleStatus::Disponible,
            'is_active' => true,
        ];
    }

    /** Véhicule en maintenance : invisible à la recherche, jamais réservable. */
    public function inMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::EnMaintenance,
        ]);
    }

    /** Véhicule retiré du site sans être supprimé. */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'transmission' => 'automatique',
        ]);
    }

    public function electric(): static
    {
        return $this->state(fn (array $attributes) => [
            'fuel' => 'electrique',
        ]);
    }

    /** Immatriculation unique : la colonne a une contrainte d'unicité. */
    protected function uniquePlate(): string
    {
        static $sequence = 0;

        $sequence++;

        return sprintf('TG-%04d-%s', $sequence, strtoupper(fake()->unique()->lexify('??')));
    }
}
