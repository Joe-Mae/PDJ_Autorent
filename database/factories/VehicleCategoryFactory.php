<?php

namespace Database\Factories;

use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VehicleCategory>
 */
class VehicleCategoryFactory extends Factory
{
    /**
     * Les prix par défaut reprennent le barème réel de l'agence : 18 000 F CFA
     * la journée pour une citadine, 60 000 F CFA de caution. Un test qui lit
     * « 18 000 » sait immédiatement de quoi il parle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Citadines', 'Berlines', 'SUV / 4x4', 'Luxe', 'Utilitaires', 'Électriques',
        ]);

        return [
            'name_fr' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name, '-'),
            'description_fr' => 'Une catégorie de l\'agence.',
            'description_en' => 'One of the agency categories.',
            'daily_price' => 18000,
            'deposit_amount' => 60000,
            'daily_price_holiday' => null,
            'seats' => 5,
            'doors' => 5,
            'bags' => 3,
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    /** Catégorie mise en avant sur la page d'accueil. */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /** Catégorie retirée du site mais gardée pour l'historique. */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
