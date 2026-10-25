<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Création et modification d'un véhicule dans l'administration.
 *
 * Régression : l'ajout d'un véhicule AVEC photo échouait en erreur 500. Le
 * nom de fichier était construit avec le helper global slug(), supprimé de
 * Laravel 11. Sans photo, la boucle d'upload ne s'exécutait pas et le défaut
 * restait invisible — d'où l'absence de test sur ce chemin.
 */
class AdminVehicleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('fleet');
    }

    public function test_la_liste_des_vehicules_garde_les_formulaires_de_statut_hors_du_formulaire_de_suppression(): void
    {
        $this->vehicle();

        $html = $this->actingAs($this->staff('gestionnaire'))
            ->get('/admin/vehicules')
            ->assertOk()
            ->getContent();

        // Les cases sont rattachées au formulaire de suppression par attribut :
        // des formulaires imbriqués feraient passer un changement de statut
        // pour une suppression en masse de la flotte.
        $this->assertStringContainsString('id="vehicles-bulk"', $html);
        $this->assertStringContainsString('form="vehicles-bulk"', $html);
    }

    public function test_ajoute_un_vehicule_sans_photo(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload())
            ->assertRedirect();

        $vehicle = Vehicle::query()->latest('id')->firstOrFail();

        $this->assertSame('Peugeot', $vehicle->brand);
        $this->assertSame('208', $vehicle->model);
        $this->assertSame('TG-9999-ZZ', $vehicle->plate);
    }

    /**
     * Le chemin qui échouait : dès la première photo, le nom de fichier était
     * calculé via un helper inexistant et la requête levait une Error.
     */
    public function test_ajoute_un_vehicule_avec_une_photo(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload([
                'photos' => [UploadedFile::fake()->image('voiture.jpg')],
            ]))
            ->assertRedirect();

        $vehicle = Vehicle::query()->latest('id')->firstOrFail();

        $this->assertCount(1, $vehicle->getMedia(Vehicle::MEDIA_GALLERY));
        Storage::disk('fleet')->assertExists($vehicle->getMedia(Vehicle::MEDIA_GALLERY)->first()->getPathRelativeToRoot());
    }

    public function test_ajoute_un_vehicule_avec_plusieurs_photos_les_nomme_sans_espace(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload([
                'photos' => [
                    UploadedFile::fake()->image('une voiture.jpg'),
                    UploadedFile::fake()->image('autre.png'),
                ],
            ]))
            ->assertRedirect();

        $vehicle = Vehicle::query()->latest('id')->firstOrFail();
        $fichiers = $vehicle->getMedia(Vehicle::MEDIA_GALLERY)->map->file_name->all();

        $this->assertCount(2, $fichiers);

        foreach ($fichiers as $nom) {
            $this->assertDoesNotMatchRegularExpression('/\s/', $nom, "Le nom « {$nom} » contient un espace.");
        }
    }

    public function test_une_immatriculation_deja_prise_est_refusee(): void
    {
        $this->vehicle(null, ['plate' => 'TG-9999-ZZ']);

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload())
            ->assertSessionHasErrors('plate');

        $this->assertSame(1, Vehicle::query()->count());
    }

    public function test_une_annee_lointaine_est_refusee(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['year' => 1990]))
            ->assertSessionHasErrors('year');
    }

    /**
     * Régression : un kilométrage laissé vide arrivait en base comme `null`, et
     * MySQL refusait l'insertion sur une colonne NOT NULL. Le formulaire
     * renvoyait une erreur 500 sans raison affichée, et le véhicule n'était
     * pas créé.
     */
    public function test_un_kilometrage_vide_cree_le_vehicule_a_zero(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['mileage' => '']))
            ->assertRedirect();

        $this->assertSame(0, Vehicle::query()->firstOrFail()->mileage);
    }

    /**
     * Régression : une case à décocher n'envoie rien. Lire la clé avec
     * `?? true` rendait « visible sur le site » incontrôlable — l'agent
     * décochait, enregistrait, et le véhicule restait visible.
     */
    public function test_decocher_visible_sur_le_site_retire_le_vehicule(): void
    {
        $vehicle = $this->vehicle();

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload([
                'mileage' => 1000,
                'is_active' => null,
            ]))
            ->assertRedirect();

        $this->assertFalse($vehicle->refresh()->is_active);
    }

    public function test_laisser_visible_sur_le_site_coche_le_laisse(): void
    {
        $vehicle = $this->vehicle();

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload([
                'mileage' => 1000,
                'is_active' => '1',
            ]))
            ->assertRedirect();

        $this->assertTrue($vehicle->refresh()->is_active);
    }

    /**
     * La caution se décide véhicule par véhicule : décocher la case doit
     * vraiment l'enlever, la recocher doit la remettre.
     */
    public function test_l_admin_desactive_puis_reactive_la_caution_d_un_vehicule(): void
    {
        $vehicle = $this->vehicle();

        $this->assertTrue($vehicle->deposit_required);

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload(['deposit_required' => null]))
            ->assertRedirect();

        $this->assertFalse($vehicle->refresh()->deposit_required);

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload(['deposit_required' => '1']))
            ->assertRedirect();

        $this->assertTrue($vehicle->refresh()->deposit_required);
    }

    public function test_un_vehicule_cree_avec_la_caution_cochee_l_exige(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['deposit_required' => '1']))
            ->assertRedirect();

        $this->assertTrue(Vehicle::query()->latest('id')->firstOrFail()->deposit_required);
    }

    /**
     * Archiver un véhicule le passe hors service ET le masque. Recocher
     * « Visible sur le site » doit annuler les deux, sinon la fiche réapparaît
     * dans la liste mais reste « non réservable ».
     */
    public function test_recocher_visible_remet_en_service_un_vehicule_archive(): void
    {
        $vehicle = $this->vehicle(null, [
            'status' => VehicleStatus::HorsService,
            'is_active' => false,
        ]);

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload(['is_active' => '1']))
            ->assertRedirect();

        $vehicle->refresh();

        $this->assertTrue($vehicle->is_active);
        $this->assertSame(VehicleStatus::Disponible, $vehicle->status);
    }

    /** Une immobilisation volontaire (panne, atelier) ne doit pas être annulée par une simple modification. */
    public function test_un_vehicule_deja_visible_et_hors_service_reste_hors_service(): void
    {
        $vehicle = $this->vehicle(null, [
            'status' => VehicleStatus::HorsService,
            'is_active' => true,
        ]);

        $this->actingAs($this->staff('gestionnaire'))
            ->put('/admin/vehicules/'.$vehicle->id, $this->payload(['is_active' => '1']))
            ->assertRedirect();

        $this->assertSame(VehicleStatus::HorsService, $vehicle->refresh()->status);
    }

    // ── Flotte recyclable : archives et remise en service ────────────────────

    /** Par défaut, l'écran ne montre que la flotte qui peut rouler. */
    public function test_la_liste_montre_la_flotte_active_par_defaut(): void
    {
        $active = $this->vehicle();
        $archived = $this->vehicle(null, [
            'status' => VehicleStatus::HorsService,
            'is_active' => false,
        ]);
        $deleted = $this->vehicle();
        $deleted->delete();

        $this->actingAs($this->staff('gestionnaire'))
            ->get('/admin/vehicules')
            ->assertOk()
            ->assertSee($active->plate)
            ->assertDontSee($archived->plate)
            ->assertDontSee($deleted->plate);
    }

    /** L'onglet « Archivés » réunit les véhicules masqués et les supprimés. */
    public function test_la_vue_archives_montre_les_masques_et_les_supprimes(): void
    {
        $active = $this->vehicle();
        $archived = $this->vehicle(null, [
            'status' => VehicleStatus::HorsService,
            'is_active' => false,
        ]);
        $deleted = $this->vehicle();
        $deleted->delete();

        $this->actingAs($this->staff('gestionnaire'))
            ->get('/admin/vehicules?vue=archives')
            ->assertOk()
            ->assertSee($archived->plate)
            ->assertSee($deleted->plate)
            ->assertDontSee($active->plate);
    }

    public function test_remettre_en_service_un_vehicule_archive(): void
    {
        $vehicle = $this->vehicle(null, [
            'status' => VehicleStatus::HorsService,
            'is_active' => false,
        ]);

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules/'.$vehicle->id.'/remettre-en-service')
            ->assertRedirect(route('admin.vehicles.index', ['vue' => 'archives']));

        $vehicle->refresh();

        $this->assertTrue($vehicle->is_active);
        $this->assertSame(VehicleStatus::Disponible, $vehicle->status);
    }

    /** Une voiture supprimée garde sa fiche et son immatriculation : on la restaure. */
    public function test_remettre_en_service_un_vehicule_supprime_le_ressuscite_sans_le_recreer(): void
    {
        $vehicle = $this->vehicle(null, ['plate' => 'TG-RECY1']);
        $vehicle->delete();

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules/'.$vehicle->id.'/remettre-en-service')
            ->assertRedirect();

        $restored = Vehicle::query()->withTrashed()->findOrFail($vehicle->id);

        $this->assertFalse($restored->trashed());
        $this->assertTrue($restored->is_active);
        $this->assertSame(VehicleStatus::Disponible, $restored->status);
        $this->assertSame('TG-RECY1', $restored->plate);
        $this->assertSame(1, Vehicle::query()->count());
    }

    public function test_remettre_en_service_une_selection_de_vehicules(): void
    {
        $first = $this->vehicle(null, ['status' => VehicleStatus::HorsService, 'is_active' => false]);
        $second = $this->vehicle(null, ['status' => VehicleStatus::HorsService, 'is_active' => false]);

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules/remettre-en-service', ['ids' => [$first->id, $second->id]])
            ->assertRedirect(route('admin.vehicles.index', ['vue' => 'archives']));

        foreach ([$first, $second] as $vehicle) {
            $vehicle->refresh();

            $this->assertTrue($vehicle->is_active);
            $this->assertSame(VehicleStatus::Disponible, $vehicle->status);
        }
    }

    public function test_un_agent_d_accueil_ne_remet_pas_un_vehicule_en_service(): void
    {
        $vehicle = $this->vehicle(null, ['status' => VehicleStatus::HorsService, 'is_active' => false]);

        $this->actingAs($this->staff('agent_accueil'))
            ->post('/admin/vehicules/'.$vehicle->id.'/remettre-en-service')
            ->assertForbidden();
    }

    // ── Photos ───────────────────────────────────────────────────────────────

    /**
     * Sans règle de validation, une photo trop lourde était refusée par PHP
     * sans qu'aucun message n'apparaisse : l'agent ne comprenait pas pourquoi
     * rien ne s'était enregistré.
     */
    public function test_une_photo_trop_lourde_est_refusee_avec_un_message(): void
    {
        // 3 Mo, au-dessus de la limite de 2 Mo.
        $lourde = UploadedFile::fake()->create('grande.jpg', 3072, 'image/jpeg');

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload([
                'photos' => [$lourde],
            ]))
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Vehicle::query()->count());
    }

    public function test_un_fichier_qui_n_est_pas_une_image_est_refuse(): void
    {
        $faux = UploadedFile::fake()->create('contrat.pdf', 40, 'application/pdf');

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['photos' => [$faux]]))
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Vehicle::query()->count());
    }

    public function test_une_photo_webp_est_acceptee(): void
    {
        $webp = UploadedFile::fake()->image('voiture.webp', 900, 600);

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['photos' => [$webp]]))
            ->assertRedirect();

        $this->assertCount(1, Vehicle::query()->firstOrFail()->getMedia(Vehicle::MEDIA_GALLERY));
    }

    public function test_trop_de_photos_sont_refusees(): void
    {
        $photos = [];
        for ($i = 0; $i < 11; $i++) {
            $photos[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $this->actingAs($this->staff('gestionnaire'))
            ->post('/admin/vehicules', $this->payload(['photos' => $photos]))
            ->assertSessionHasErrors('photos');

        $this->assertSame(0, Vehicle::query()->count());
    }

    /**
     * Le parc et les tarifs appartiennent a la direction : un agent d'accueil
     * qui gere le guichet ne decide pas de ce que l'agence prete, ni a quel
     * prix. Le perimetre est teste en detail dans StaffTierTest ; ici on
     * verifie juste que la creation passe bien par cette barriere.
     */
    public function test_un_agent_d_accueil_ne_cree_pas_de_vehicule(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->post('/admin/vehicules', $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Vehicle::query()->count());
    }

    public function test_un_client_ne_cree_pas_de_vehicule(): void
    {
        $this->actingAs($this->customer())
            ->post('/admin/vehicules', $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Vehicle::query()->count());
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    protected function payload(array $ajouts = []): array
    {
        return [
            'brand' => 'Peugeot',
            'model' => '208',
            'plate' => 'TG-9999-ZZ',
            'year' => 2024,
            'category_id' => $this->category()->id,
            'transmission' => 'manuelle',
            'fuel' => 'essence',
            'seats' => 5,
            'doors' => 5,
            'bags' => 3,
            'mileage' => 12000,
            'is_active' => '1',
            ...$ajouts,
        ];
    }
}
