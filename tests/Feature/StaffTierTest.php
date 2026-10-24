<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\VehicleCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les trois périmètres de l'administration.
 *
 * Régression : EnsureStaff répondait seulement « est-ce un employé ? ». Les
 * trois rôles avaient donc le même accès complet — un agent d'accueil pouvait
 * modifier un tarif journalier, retirer un véhicule du parc ou changer les
 * horaires de l'agence. Les routes documentaient trois périmètres qui
 * n'existaient nulle part dans le code.
 *
 * Ces tests tiennent la matrice entière : un défaut de rôle se voit
 * dans le nom du cas qui échoue.
 */
class StaffTierTest extends TestCase
{
    /** écrans du guichet : ouverts à l'agent d'accueil. */
    public static function ecransAgent(): array
    {
        return [
            'tableau de bord' => ['/admin'],
            'réservations' => ['/admin/reservations'],
            'documents' => ['/admin/documents'],
            'caisse' => ['/admin/paiements'],
            'clients' => ['/admin/clients'],
            'écran de retrait' => ['/agent/retrait'],
            'écran de retour' => ['/agent/retour'],
        ];
    }

    /** Catalogue et tarifs : gestionnaire. */
    public static function ecransGestionnaire(): array
    {
        return [
            'véhicules' => ['/admin/vehicules'],
            'catégories et tarifs' => ['/admin/categories'],
            'planning' => ['/admin/calendrier'],
            'statistiques' => ['/admin/statistiques'],
            'chauffeurs' => ['/admin/chauffeurs'],
            'contenus du site' => ['/admin/contenus'],
        ];
    }

    /** Réglages de l'agence : super-admin seulement. */
    public static function ecransSuperAdmin(): array
    {
        return [
            'paramètres' => ['/admin/parametres'],
        ];
    }

    #[DataProvider('ecransAgent')]
    public function test_l_agent_d_accueil_atteint_les_ecrans_du_guichet(string $url): void
    {
        $this->actingAs($this->staff('agent_accueil'))->get($url)->assertOk();
    }

    #[DataProvider('ecransGestionnaire')]
    public function test_l_agent_d_accueil_atteint_le_catalogue(string $url): void
    {
        $this->actingAs($this->staff('agent_accueil'))->get($url)->assertForbidden();
    }

    #[DataProvider('ecransSuperAdmin')]
    public function test_l_agent_d_accueil_atteint_les_parametres(string $url): void
    {
        $this->actingAs($this->staff('agent_accueil'))->get($url)->assertForbidden();
    }

    #[DataProvider('ecransGestionnaire')]
    public function test_le_gestionnaire_atteint_le_catalogue(string $url): void
    {
        $this->actingAs($this->staff('gestionnaire'))->get($url)->assertOk();
    }

    #[DataProvider('ecransSuperAdmin')]
    public function test_le_gestionnaire_atteint_les_parametres(string $url): void
    {
        $this->actingAs($this->staff('gestionnaire'))->get($url)->assertForbidden();
    }

    #[DataProvider('ecransAgent')]
    public function test_le_super_admin_atteint_le_guichet(string $url): void
    {
        $this->actingAs($this->staff('super_admin'))->get($url)->assertOk();
    }

    #[DataProvider('ecransGestionnaire')]
    public function test_le_super_admin_atteint_le_catalogue(string $url): void
    {
        $this->actingAs($this->staff('super_admin'))->get($url)->assertOk();
    }

    #[DataProvider('ecransSuperAdmin')]
    public function test_le_super_admin_atteint_les_parametres(string $url): void
    {
        $this->actingAs($this->staff('super_admin'))->get($url)->assertOk();
    }

    // ── Écritures ────────────────────────────────────────────────────────────

    public function test_l_agent_d_accueil_ne_change_pas_un_tarif(): void
    {
        $category = $this->category();

        $this->actingAs($this->staff('agent_accueil'))
            ->put('/admin/categories/'.$category->slug, $this->categoryPayload($category, 99000))
            ->assertForbidden();

        $this->assertSame(
            self::DAILY_PRICE,
            $category->refresh()->daily_price,
            'Le tarif ne doit pas avoir bougé.',
        );
    }

    public function test_le_gestionnaire_change_un_tarif(): void
    {
        $category = $this->category();

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/categories/'.$category->slug, $this->categoryPayload($category, 21000))
            ->assertRedirect();

        $this->assertSame(21000, $category->refresh()->daily_price);
    }

    public function test_le_gestionnaire_ne_change_pas_les_horaires_de_l_agence(): void
    {
        $this->settingRow('agency.open_time', '08:00', 'general', "Heure d'ouverture");

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/parametres', ['settings' => ['agency.open_time' => '24:00']])
            ->assertForbidden();

        $this->assertSame('08:00', $this->settings()->string('agency.open_time'));
    }

    public function test_le_super_admin_change_les_horaires_de_l_agence(): void
    {
        $this->settingRow('agency.open_time', '08:00', 'general', "Heure d'ouverture");

        $this->actingAs($this->staff('super_admin'))
            ->post('/admin/parametres', ['settings' => ['agency.open_time' => '07:30']])
            ->assertRedirect();

        $this->assertSame('07:30', $this->settings()->string('agency.open_time'));
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    /**
     * Le contrôleur des réglages n'écrit que sur une ligne existante : un
     * réglage déclaré mais jamais créé est invisible et non modifiable. Le
     * test crée donc la ligne, comme le fait le seeder en installation.
     *
     * @return array<string, mixed>
     */
    protected function settingRow(string $key, string $value, string $group, string $label): Setting
    {
        return Setting::query()->create([
            'key' => $key,
            'value' => $value,
            'group' => $group,
            'label_fr' => $label,
            'type' => 'string',
        ]);
    }

    /**
     * Les catégories se routent par slug, pas par identifiant.
     *
     * @return array<string, mixed>
     */
    protected function categoryPayload(VehicleCategory $category, int $dailyPrice): array
    {
        return [
            'name_fr' => $category->name_fr,
            'name_en' => $category->name_en,
            'slug' => $category->slug,
            'daily_price' => $dailyPrice,
            'deposit_amount' => $category->deposit_amount,
            'seats' => $category->seats,
            'doors' => $category->doors,
            'bags' => $category->bags,
            'sort_order' => $category->sort_order,
            'is_active' => '1',
        ];
    }

    public function test_l_agent_d_accueil_ne_requalifie_pas_un_vehicule(): void
    {
        $vehicle = $this->vehicle();

        $this->actingAs($this->staff('agent_accueil'))
            ->post('/admin/vehicules/'.$vehicle->id.'/statut', ['status' => 'hors_service'])
            ->assertForbidden();

        $this->assertSame('disponible', $vehicle->refresh()->status->value);
    }

    public function test_un_client_ne_bascule_jamais_d_un_perimetre_a_l_autre(): void
    {
        $customer = $this->customer();

        foreach (['/admin', '/admin/vehicules', '/admin/parametres'] as $url) {
            $this->actingAs($customer)->get($url)->assertForbidden();
        }
    }

    // ── Menu ─────────────────────────────────────────────────────────────────

    /** Un lien vers un écran interdit ne doit pas exister dans le menu. */
    public function test_le_menu_de_l_agent_ne_propose_pas_le_catalogue(): void
    {
        $html = $this->actingAs($this->staff('agent_accueil'))->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.vehicles.index'), $html);
        $this->assertStringNotContainsString(route('admin.settings.index'), $html);
        $this->assertStringContainsString(route('admin.bookings.index'), $html);
    }

    public function test_le_menu_du_gestionnaire_propose_le_catalogue_mais_pas_les_parametres(): void
    {
        $html = $this->actingAs($this->staff('gestionnaire'))->get('/admin')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.vehicles.index'), $html);
        $this->assertStringNotContainsString(route('admin.settings.index'), $html);
    }

    public function test_le_menu_du_super_admin_propose_tout(): void
    {
        $html = $this->actingAs($this->staff('super_admin'))->get('/admin')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.vehicles.index'), $html);
        $this->assertStringContainsString(route('admin.calendar'), $html);
        $this->assertStringContainsString(route('admin.customers.index'), $html);
        $this->assertStringContainsString(route('admin.statistics.index'), $html);
        $this->assertStringContainsString(route('admin.settings.index'), $html);
        $this->assertStringNotContainsString('class="admin-footer-link"', $html);
        $this->assertStringContainsString(route('logout', ['locale' => 'fr']), $html);
    }

    public function test_le_menu_affiche_le_perimetre_de_la_personne(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Agent d\'accueil');

        $this->actingAs($this->staff('super_admin'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Super administrateur');
    }
}
