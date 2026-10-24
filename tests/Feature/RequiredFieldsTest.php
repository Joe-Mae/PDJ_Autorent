<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Les champs obligatoires des formulaires publics.
 *
 * Le navigateur ne fait pas confiance au JavaScript : si la validation côté
 * client est contournée — script bloqué, désactivé, ou appelée en direct —
 * le serveur doit refuser l'envoi de la même façon. Ces tests prouvent que la
 * règle existe des deux côtés.
 */
class RequiredFieldsTest extends TestCase
{
    public function test_le_message_obligatoire_est_traduit_dans_les_deux_langues(): void
    {
        foreach (['fr', 'en'] as $locale) {
            app()->setLocale($locale);

            $message = __('general.required_field');

            $this->assertNotSame('general.required_field', $message, "Clé absente en {$locale}");
            $this->assertNotSame('', trim($message));
        }

        $this->assertSame('Information requise', __('general.required_field', [], 'fr'));
    }

    public function test_le_message_est_transmis_a_la_page_pour_le_javascript(): void
    {
        $html = $this->get('/fr/connexion')->assertOk()->getContent();

        $this->assertStringContainsString('window.PDJ', $html);
        $this->assertStringContainsString('Information requise', $html);
    }

    // ── Le serveur reste la porte ────────────────────────────────────────────

    public function test_le_serveur_refuse_une_reservation_sans_dates(): void
    {
        $vehicle = $this->vehicle();

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => '',
            'au' => '',
            'heure_depart' => '',
            'heure_retour' => '',
        ])->assertSessionHasErrors(['du', 'au', 'heure_depart', 'heure_retour']);
    }

    public function test_le_serveur_refuse_un_vehicule_sans_immatriculation(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', [
                'brand' => 'Peugeot',
                'model' => '208',
                'plate' => '',
                'year' => 2024,
                'category_id' => $this->category()->id,
                'transmission' => 'manuelle',
                'fuel' => 'essence',
                'seats' => 5,
                'doors' => 5,
                'bags' => 3,
            ])
            ->assertSessionHasErrors('plate');
    }

    public function test_le_bloc_de_recherche_garde_ses_dates_par_defaut(): void
    {
        $html = $this->get('/fr')->assertOk()->getContent();

        // Le bloc de recherche est pré-rempli : aucun message ne doit
        // apparaître dessus, et le bouton doit rester actif.
        $this->assertStringContainsString('value="'.now()->addDay()->toDateString().'"', $html);
        $this->assertStringNotContainsString('pdj-required-msg', $html);
    }
}
