<?php

namespace Tests;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Socle des tests fonctionnels.
 *
 * setUp() ne contient que de la configuration : les enregistrements sont
 * créés par les helpers ci-dessous, appelés explicitement par chaque test.
 * Un enregistrement fabriqué en setUp() resterait en mémoire jusqu'à la fin
 * de la suite et serait partagé entre tests.
 *
 * Le barème de référence est volontairement lisible : 18 000 F CFA le jour,
 * 60 000 F CFA de caution, 7 000 F CFA le bloc de chauffeur de 4 h.
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshDatabase;

    /**
     * Constantes de référence : un test qui lit « 18000 » sait ce qu'il vérifie,
     * et ne peut pas passer pour un tautologie en recalculant la même chose
     * avec la logique de l'implémentation.
     */
    protected const DAILY_PRICE = 18000;

    protected const DEPOSIT = 60000;

    protected const DRIVER_BLOCK_PRICE = 7000;

    protected const DRIVER_BLOCK_HOURS = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedSettings();
    }

    // ── Données d'appui ──────────────────────────────────────────────────────

    /** Catégorie au barème connu. */
    protected function category(array $attributes = []): VehicleCategory
    {
        return VehicleCategory::factory()->create([
            'daily_price' => self::DAILY_PRICE,
            'deposit_amount' => self::DEPOSIT,
            'seats' => 5,
            'doors' => 5,
            'bags' => 3,
            ...$attributes,
        ]);
    }

    /** Véhicule disponible, dans la catégorie fournie ou dans une catégorie neuve. */
    protected function vehicle(?VehicleCategory $category = null, array $attributes = []): Vehicle
    {
        return Vehicle::factory()->create([
            'category_id' => ($category ?? $this->category())->id,
            ...$attributes,
        ]);
    }

    /** Client inscrit, non connecté : les tests d'éligibilité partent de là. */
    protected function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /** Compte du personnel. Le rôle est celui des trois rôles d'agence. */
    protected function staff(string $role = 'agent_accueil', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        $user->assignRole($role);

        return $user;
    }

    protected function driver(array $attributes = []): Driver
    {
        return Driver::factory()->create([
            'status' => DriverStatus::Actif,
            ...$attributes,
        ]);
    }

    /**
     * Créneau de N jours, 8 h → 19 h, à partir de J+$fromNow.
     * 8 h → 19 h = 11 h = 3 blocs de chauffeur par jour, entier et lisible.
     */
    protected function period(int $days = 4, int $fromNow = 1): BookingPeriod
    {
        $startsAt = now()->addDays($fromNow)->startOfDay();

        return BookingPeriod::make(
            $startsAt->toDateString(),
            $startsAt->copy()->addDays($days)->toDateString(),
            8,
            19,
        );
    }

    // ── Services ─────────────────────────────────────────────────────────────

    protected function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    protected function pricing(): PricingService
    {
        return app(PricingService::class);
    }

    protected function availability(): AvailabilityService
    {
        return app(AvailabilityService::class);
    }

    protected function bookings(): BookingService
    {
        return app(BookingService::class);
    }

    // ── Configuration ────────────────────────────────────────────────────────

    /**
     * Les trois rôles de l'application. Les permissions ne sont pas
     * utilisées : l'accès au back-office passe par EnsureStaff et le rôle,
     * testés séparément.
     */
    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['super_admin', 'gestionnaire', 'agent_accueil'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    /**
     * Les réglages par défaut du service sont déjà sensés (18 000 F CFA, 4 h de
     * bloc, 120 km/h…). On n'écrit que ce qu'un test modifie, pour que la
     * suite suive levolution du barème au lieu de le figer.
     */
    private function seedSettings(): void
    {
        $this->settings()->flush();
    }
}
