<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DemoSeeder::class,
            SampleBookingsSeeder::class,
        ]);

        $this->seedStaff();
    }

    /**
     * Trois comptes de démonstration, un par rôle. Le mot de passe est le même
     * pour les trois : en développement, un mot de passe différent par rôle
     * fait perdre plus de temps qu'il n'en fait gagner.
     */
    private function seedStaff(): void
    {
        $staff = [
            ['Super', 'Admin', 'super@pdj-autorent.tg', 'super_admin'],
            ['Sylvie', 'Kponou', 'gestion@pdj-autorent.tg', 'gestionnaire'],
            ['Aline', 'Dosso', 'agent@pdj-autorent.tg', 'agent_accueil'],
        ];

        foreach ($staff as [$first, $last, $email, $role]) {
            $user = User::query()->create([
                'name' => "{$first} {$last}",
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => '+22890'.fake()->unique()->numerify('######'),
                'birth_date' => now()->subYears(35),
                'password' => Hash::make('pdjautorent'),
                'preferred_locale' => 'fr',
            ]);

            $user->assignRole($role);
        }
    }
}
