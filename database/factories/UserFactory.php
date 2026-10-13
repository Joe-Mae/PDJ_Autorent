<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Un client togolais : nom, prénom, téléphone au format E.164 (+228),
     * date de naissance. L'e-mail reste rempli parce que la colonne est unique
     * et non nulle ; il sert de repli dans l'interface.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $this->uniquePhone(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years')->format('Y-m-d'),
            'preferred_locale' => 'fr',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Client de moins de l'âge minimum : la réservation doit être refusée.
     */
    public function underage(int $years = 16): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => now()->subYears($years)->toDateString(),
        ]);
    }

    /**
     * Client bloqué après deux absences : toute réservation est refusée
     * tant que la caution d'avance n'est pas exigée.
     */
    public function blocked(int $absences = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'no_show_count' => $absences,
            'blocked_at' => now(),
            'block_reason' => 'Deux absences de suite.',
        ]);
    }

    /**
     * Téléphone unique : la colonne a une contrainte d'unicité.
     *
     * Format E.164 sans le « + », soit 22890123499 : c'est exactement ce que
     * produit AuthController::normalizePhone et ce que findByIdentifier
     * recherche. Avec le « + », la connexion par numéro échouerait.
     */
    protected function uniquePhone(): string
    {
        static $sequence = 0;

        $sequence++;

        return sprintf('22890%08d', $sequence);
    }
}
