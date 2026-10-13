<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingsService;
use Database\Seeders\DemoSeeder;
use Tests\TestCase;

/**
 * Écriture des réglages de l'agence.
 *
 * Chaque réglage modifie une règle métier — le prix d'un bloc de chauffeur,
 * l'âge minimum, le nombre d'absences avant blocage. Une écriture qui échoue
 * en silence laisse l'agence croire qu'elle a changé une règle alors que
 * l'ancienne s'applique toujours. Ces tests vérifient que l'écriture aboutit
 * vraiment et qu'elle est visible au bon endroit.
 */
class SettingsServiceTest extends TestCase
{
    public function test_ecrit_un_reglage_deja_present_en_base(): void
    {
        $this->settings()->put('driver.block_price', 7500);

        $this->assertSame(7500, $this->settings()->int('driver.block_price'));
    }

    /**
     * Régression : la colonne label_fr est NOT NULL. Écrire une clé absente de
     * la base sans fournir de libellé levait une erreur d'intégrité, et
     * l'écriture était perdue. Or c'est le mécanisme prévu pour ajouter un
     * nouveau réglage.
     */
    public function test_ecrit_un_reglement_absent_de_la_base_sans_echouer(): void
    {
        $this->settings()->put('agency.opening_note', 'Ouvert le samedi matin.');

        $this->assertDatabaseHas('settings', [
            'key' => 'agency.opening_note',
            'value' => 'Ouvert le samedi matin.',
        ]);
    }

    public function test_un_reglement_absent_de_la_base_herite_de_sa_valeur_par_defaut(): void
    {
        // Aucune ligne en base : c'est le défaut du service qui fait foi.
        $this->assertSame(120, $this->settings()->int('booking.speed_limit_kmh'));
        $this->assertSame(18, $this->settings()->int('booking.min_age'));
        $this->assertSame(5000, $this->settings()->int('driver.block_price'));
    }

    public function test_apres_ecriture_la_valeur_lit_en_base_prime_sur_le_defaut(): void
    {
        $this->assertSame(120, $this->settings()->int('booking.speed_limit_kmh'));

        $this->settings()->put('booking.speed_limit_kmh', 90);

        $this->assertSame(90, $this->settings()->int('booking.speed_limit_kmh'));
    }

    public function test_ecrire_plusieurs_reglements_en_une_seule_appel(): void
    {
        $this->settings()->putMany([
            'booking.min_age' => 21,
            'booking.max_active_bookings' => 1,
        ]);

        $this->assertSame(21, $this->settings()->int('booking.min_age'));
        $this->assertSame(1, $this->settings()->int('booking.max_active_bookings'));
    }

    public function test_le_type_declar_determine_la_conversion(): void
    {
        $this->settings()->put('driver.block_price', '6000');
        $this->assertSame(6000, $this->settings()->int('driver.block_price'));

        $this->settings()->put('document.selfie_enabled', '0');
        $this->assertFalse($this->settings()->bool('document.selfie_enabled'));
    }

    public function test_apres_vidage_de_cache_la_valeur_ecrite_relue_en_base(): void
    {
        $this->settings()->put('agency.phone', '+228 90 99 88 77');

        $this->settings()->flush();

        $this->assertSame('+228 90 99 88 77', $this->settings()->string('agency.phone'));
    }

    /**
     * Régression : la colonne label_fr est NOT NULL, si bien qu'écrire une clé
     * absente de la base sans libellé levait une erreur d'intégrité et perdait
     * l'écriture. Le test parcourt toutes les clés déclarées : il attrape
     * n'importe quelle clé future qui n'aurait pas été prévue dans put().
     */
    public function test_tout_reglement_declare_peut_etre_ecrit_puis_relu(): void
    {
        $echecs = [];

        foreach (array_keys(SettingsService::DEFAULTS) as $key) {
            try {
                $this->settings()->put($key, '7');
            } catch (\Throwable $e) {
                $echecs[] = $key.' ('.$e::class.')';

                continue;
            }

            if (Setting::query()->where('key', $key)->value('value') !== '7') {
                $echecs[] = $key.' (non relu)';
            }
        }

        $this->assertSame([], $echecs, "Réglages non accessibles en écriture :\n  ".implode("\n  ", $echecs));
    }

    /**
     * L'écran /admin/parametres n'affiche que les lignes existantes et ignore
     * silencieusement une clé absente à l'enregistrement. Un réglage déclaré
     * dont la ligne manque n'est donc pas modifiable : le seeder doit en créer
     * une par clé déclarée.
     */
    public function test_le_seeder_cree_une_ligne_pour_tout_reglement_declare(): void
    {
        $this->seed(DemoSeeder::class);

        $absents = array_values(array_diff(
            array_keys(SettingsService::DEFAULTS),
            Setting::query()->pluck('key')->all(),
        ));

        $this->assertSame(
            [],
            $absents,
            'Déclarés mais sans ligne en base : ils n\'apparaissent pas dans /admin/parametres. '
            .'Ajoutez-les au seeder. -> '.implode(', ', $absents),
        );
    }
}
