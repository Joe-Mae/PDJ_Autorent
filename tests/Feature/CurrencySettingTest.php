<?php

namespace Tests\Feature;

use App\Services\SettingsService;
use Tests\TestCase;

/**
 * Devise affichée, réglable par l'agence.
 *
 * Un blanc-marque hors du Franc CFA ne doit toucher à aucun fichier : il
 * change `general.currency`, et tout le reste suit. Ce test verrouille ce
 * lien — un montant recodé en dur passerait inaperçu tant qu'on affiche des
 * FCFA, et sauterait à la première vente en autre devise.
 */
class CurrencySettingTest extends TestCase
{
    public function test_les_montants_de_la_page_d_accueil_suivent_le_reglage(): void
    {
        $this->settings()->put('general.currency', 'XOF');

        $this->get('/fr')
            ->assertOk()
            ->assertSee('XOF')
            ->assertDontSee('FCFA');
    }

    /** Les conditions générales citent les tarifs chauffeur : eux aussi suivent. */
    public function test_les_tarifs_des_conditions_generales_suivent_le_reglage(): void
    {
        $this->settings()->put('general.currency', 'XOF');

        $this->get('/fr/conditions-generales')
            ->assertOk()
            ->assertSee('15 000 XOF')
            ->assertSee('13 000 XOF')
            ->assertSee('7 000 XOF')
            ->assertDontSee('FCFA');
    }

    public function test_money_formate_un_montant_avec_la_devise_en_cours(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame('150 000 FCFA', $settings->money(150000));

        $settings->put('general.currency', 'XOF');

        $this->assertSame('150 000 XOF', $settings->money(150000));
        // Jamais de décimale : un tarif est un entier du début à la fin.
        $this->assertSame('7 000 XOF', $settings->money(7000));
    }

    /** Les libellés de réglages monétaire portent le placeholder :currency. */
    public function test_l_ecran_des_reglages_affiche_la_devise_en_cours(): void
    {
        $this->settings()->put('general.currency', 'XOF');

        $this->actingAs($this->staff('super_admin'))
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Chauffeur seul : tarif journalier (XOF)')
            ->assertDontSee('(:currency)');
    }
}
