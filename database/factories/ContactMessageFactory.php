<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Un message de contact tel qu'il arrive depuis la page publique : un nom,
     * un téléphone, parfois un e-mail, et le texte libre du visiteur.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+228 90 '.fake()->numerify('## ## ##'),
            'email' => fake()->optional()->safeEmail(),
            'message' => fake()->paragraph(),
            'status' => 'nouveau',
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lu',
            'handled_at' => now(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'traite',
            'handled_at' => now(),
        ]);
    }
}
