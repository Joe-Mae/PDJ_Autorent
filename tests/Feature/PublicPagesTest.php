<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les pages publiques, dans les deux langues.
 *
 * Le site est bilingual : chaque page doit exister en français et en anglais,
 * sous le même chemin, et la bascule de langue doit produire une adresse qui
 * existe réellement. Un lien de langue cassé renvoie 404 : c'est le défaut le
 * plus visible de tout le site, et il est invisible sans test.
 */
class PublicPagesTest extends TestCase
{
    /** Chemins des pages publiques, sans le préfixe de langue. */
    public static function publicPages(): array
    {
        return [
            'accueil' => ['/'],
            'la flotte' => ['/vehicules'],
            'questions fréquentes' => ['/faq'],
            'avis clients' => ['/avis'],
            "l'agence" => ['/l-agence'],
            'contact' => ['/contact'],
            'la caution' => ['/caution'],
            'comment payer' => ['/comment-payer'],
            'avec chauffeur' => ['/location-avec-chauffeur'],
            'conditions générales' => ['/conditions-generales'],
            "l'assurance" => ['/assurance'],
            'confidentialité' => ['/confidentialite'],
            'mentions légales' => ['/mentions-legales'],
            'connexion' => ['/connexion'],
        ];
    }

    public static function navigationLocales(): array
    {
        return [
            'français' => ['fr', 'Accueil', 'Garage'],
            'anglais' => ['en', 'Home', 'The fleet'],
        ];
    }

    #[DataProvider('navigationLocales')]
    public function test_navigation_renders_home_and_vehicle_links_in_each_locale(
        string $locale,
        string $homeLabel,
        string $fleetLabel,
    ): void {
        $this->get('/'.$locale)
            ->assertSee('href="'.route('home', ['locale' => $locale]).'"', escape: false)
            ->assertSee('href="'.route('vehicles.index', ['locale' => $locale]).'"', escape: false)
            ->assertSee($homeLabel)
            ->assertSee($fleetLabel);
    }

    public function test_navigation_ne_displays_plus_le_bouton_de_connexion(): void
    {
        $this->get('/fr')
            ->assertDontSee('href="'.route('login', ['locale' => 'fr']).'"', escape: false)
            ->assertDontSee(__('nav.login'));
    }

    public function test_le_personnel_peut_ouvrir_l_administration_depuis_le_pied_de_page(): void
    {
        $this->actingAs($this->staff())
            ->get('/fr')
            ->assertOk()
            ->assertSee('class="admin-footer-link"', escape: false)
            ->assertSee('href="'.route('admin.dashboard').'"', escape: false)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee('rel="noopener noreferrer"', escape: false)
            ->assertSee('>Admin</a>', escape: false);
    }

    public function test_le_lien_admin_discret_est_visible_sur_le_site_public(): void
    {
        $this->get('/fr')
            ->assertOk()
            ->assertSee('class="admin-footer-link"', escape: false)
            ->assertSee('href="'.route('admin.dashboard').'"', escape: false)
            ->assertSee('>Admin</a>', escape: false);

        $this->actingAs($this->customer())
            ->get('/fr')
            ->assertOk()
            ->assertSee('class="admin-footer-link"', escape: false);
    }

    public function test_la_liste_garage_ne_propose_pas_de_bouton_voir_cette_voiture(): void
    {
        $this->vehicle();

        $this->get('/fr/vehicules')
            ->assertSee(__('general.book'))
            ->assertDontSee(__('general.see_vehicle'));
    }

    public function test_homepage_renders_an_accessible_photo_carousel_from_vehicle_gallery(): void
    {
        Storage::fake('fleet');
        $vehicleIds = [];

        foreach (['hero-one.jpg', 'hero-two.jpg'] as $photo) {
            $vehicle = $this->vehicle();
            $vehicle->addMedia(UploadedFile::fake()->image($photo))
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
            $vehicleIds[] = $vehicle->id;
        }

        $this->settings()->put('site.hero_vehicle_ids', $vehicleIds);

        $this->get('/fr')
            ->assertSee('class="pdj-hero home-hero"', escape: false)
            ->assertSee('data-hero-carousel', escape: false)
            ->assertSee('data-hero-slide', escape: false)
            ->assertSee('data-hero-previous', escape: false)
            ->assertSee('data-hero-next', escape: false)
            ->assertSee('data-hero-toggle', escape: false)
            ->assertSee('data-hero-dot="1"', escape: false)
            ->assertSee('Photo précédente')
            ->assertSee('Photo suivante')
            ->assertSee('Mettre le diaporama en pause');
    }

    /**
     * L'écran d'apparence promet « les photos des véhicules » : sélectionner
     * une voiture doit reprendre toute sa galerie, pas seulement sa photo
     * principale. Sinon quatre photos importées ne donnent qu'une slide.
     */
    public function test_le_diaporama_reprend_toutes_les_photos_d_un_vehicule_selectionne(): void
    {
        Storage::fake('fleet');
        $vehicle = $this->vehicle();

        foreach (['avant.jpg', 'arriere.jpg', 'interieur.jpg', 'cote.jpg'] as $photo) {
            $vehicle->addMedia(UploadedFile::fake()->image($photo))
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        }

        $this->settings()->put('site.hero_vehicle_ids', [$vehicle->id]);
        $this->settings()->put('site.hero_uploaded_images', []);

        $response = $this->get('/fr')->assertOk();
        $html = $response->getContent();

        $this->assertSame(4, substr_count($html, 'data-hero-slide'));
        $this->assertSame(4, substr_count($html, 'data-hero-dot='));

        foreach ($vehicle->fresh()->galleryPhotos() as $media) {
            $response->assertSee($media->getUrl(), escape: false);
        }
    }

    public function test_homepage_does_not_add_vehicle_photos_to_an_unconfigured_slideshow(): void
    {
        Storage::fake('fleet');
        $vehicle = $this->vehicle();
        $vehicle->addMedia(UploadedFile::fake()->image('not-selected.jpg'))
            ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        $this->settings()->put('site.hero_vehicle_ids', []);
        $this->settings()->put('site.hero_uploaded_images', []);

        $this->get('/fr')
            ->assertOk()
            ->assertDontSee('data-hero-slide', escape: false)
            ->assertDontSee('data-hero-next', escape: false);
    }

    /**
     * Le chauffeur se réserve aussi sans voiture : l'accueil doit le dire
     * noir sur blanc, mariages compris, avec les deux tarifs.
     */
    public function test_l_accueil_presente_le_chauffeur_avec_ou_sans_voiture(): void
    {
        $this->settings()->put('driver.block_price', 7000);
        $this->settings()->put('driver.solo_daily_price', 15000);
        $this->settings()->put('driver.solo_discount_daily_price', 13000);
        $this->settings()->put('driver.solo_discount_after_days', 3);

        $this->get('/fr')
            ->assertOk()
            ->assertSee(__('driver.premium_badge'))
            ->assertSee(__('driver.title'))
            ->assertSee('Mariages et grandes occasions')
            ->assertSee('nous sommes disponibles')
            ->assertSee('7 000')
            ->assertSee('15 000')
            ->assertSee('13 000')
            ->assertSee(route('page.driver', ['locale' => 'fr']));

        $this->get('/en')
            ->assertOk()
            ->assertSee(__('driver.premium_badge'))
            ->assertSee('Weddings and big occasions');
    }

    public static function contactLocales(): array
    {
        return [
            'français' => ['fr', 'Horaires', 'Notre flotte'],
            'anglais' => ['en', 'Opening hours', 'Our fleet'],
        ];
    }

    #[DataProvider('contactLocales')]
    public function test_contact_page_shows_agency_details_without_vehicle_categories(
        string $locale,
        string $hoursLabel,
        string $fleetLabel,
    ): void {
        $this->get('/'.$locale.'/contact')
            ->assertSee($hoursLabel)
            ->assertSee('08:00 – 19:00')
            ->assertSee('https://www.google.com/maps/search/?api=1&query=', escape: false)
            ->assertDontSee($fleetLabel)
            ->assertDontSee(route('vehicles.category', ['locale' => $locale, 'category' => 'city-cars']));
    }

    public function test_les_pages_d_information_sont_affichees_dans_leur_conteneur_commun(): void
    {
        $this->get('/fr/faq')
            ->assertOk()
            ->assertSee('class="site-shell public-site structured-pages"', escape: false)
            ->assertSee('page-content', escape: false)
            ->assertSee('container-fluid', escape: false)
            ->assertSee('Questions fréquentes');
    }

    public function test_la_page_flotte_conserve_sa_mise_en_page(): void
    {
        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertDontSee('class="structured-pages"', escape: false);
    }

    public function test_les_pages_de_l_equipe_utilisent_le_gabarit_structure(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('class="structured-pages"', escape: false)
            ->assertSee('class="panel-content p-3 p-lg-4"', escape: false);
    }

    #[DataProvider('publicPages')]
    public function test_chaque_page_publique_repond_200_dans_les_deux_langues(string $chemin): void
    {
        $this->get('/fr'.$chemin)->assertOk();
        $this->get('/en'.$chemin)->assertOk();
    }

    /**
     * Régression : le lien « La flotte » du menu ne passe aucune date. Le
     * contrôleur laissait alors la période à null et renvoyait une liste
     * vide — la page affichait « 0 véhicule ». Le visiteur arrivait par la
     * porte la plus cliquée du site et repartait sans rien.
     */
    public function test_la_flotte_sans_dates_compte_les_vehicules_libres(): void
    {
        $this->category();
        $this->vehicle();
        $this->vehicle();

        $contenu = $this->get('/fr/vehicules')->assertOk()->getContent();

        $this->assertStringNotContainsString('0 véhicules', $this->texteVisible($contenu));
        $this->assertMatchesRegularExpression(
            '/data-results-count>\s*2\s*</',
            $contenu,
            'La page doit annoncer les deux véhicules libres sur le créneau par défaut.',
        );
    }

    /**
     * Une voiture partie avec un client reste au catalogue, avec un badge
     * « indisponible » : le client sait qu'elle existe et reviendra quand elle
     * sera libre. Le statut `en_location` est posé au retrait et retiré au
     * retour, donc la flotte se met à jour seule.
     */
    public function test_un_vehicule_en_location_reste_visible_mais_indisponible(): void
    {
        $sortie = $this->vehicle(null, [
            'brand' => 'BMW',
            'model' => 'X7',
            'status' => VehicleStatus::EnLocation,
        ]);
        $this->vehicle(null, ['brand' => 'Toyota', 'model' => 'Vitz']);

        $contenu = $this->get('/fr/vehicules')->assertOk()->getContent();

        $this->assertStringContainsString('BMW X7', $contenu);
        $this->assertStringContainsString('Toyota Vitz', $contenu);
        $this->assertStringContainsString(__('general.unavailable'), $contenu);
        $this->assertMatchesRegularExpression(
            '/data-results-count>\s*2\s*</',
            $contenu,
            'Les deux voitures sont au catalogue, occupée ou non.',
        );

        $this->get('/fr/vehicules/'.$sortie->id)
            ->assertOk()
            ->assertSee(__('vehicle.occupied'))
            ->assertSee('disabled', false);
    }

    public function test_un_vehicule_redevient_reservable_des_son_retour(): void
    {
        $vehicle = $this->vehicle(null, ['status' => VehicleStatus::EnLocation]);

        $this->get('/fr/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertSee(__('vehicle.occupied'));

        $vehicle->update(['status' => VehicleStatus::Disponible]);

        $this->get('/fr/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertDontSee(__('vehicle.occupied'));

        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertDontSee(__('general.unavailable'));
    }

    /** Une voiture déjà réservée sur les dates demandées est marquée occupée. */
    public function test_un_vehicule_pris_sur_la_periode_porte_le_badge_indisponible(): void
    {
        $vehicle = $this->vehicle();
        Booking::factory()->confirmed()->on($vehicle)->create();

        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertSee($vehicle->displayName())
            ->assertSee(__('general.unavailable'));
    }

    /**
     * Les lignes de la liste sont rechargées par le formulaire de filtres.
     * Sans dates, ce rechargement doit ramener la flotte, pas une page vide.
     * Régression : l'endpoint lisait `du` et `au` dans un tableau qui ne les
     * contenait pas, et renvoyait une erreur 500.
     */
    public function test_le_rechargement_des_filtres_sans_dates_rend_la_flotte(): void
    {
        $category = $this->category();
        $this->vehicle($category);
        $this->vehicle($category);

        $reponse = $this->post('/fr/vehicules/filtres')->assertOk();

        $donnees = json_decode($reponse->getContent(), true);

        $this->assertSame(2, $donnees['count']);
        $this->assertSame(2, substr_count($donnees['html'], 'vehicle-row__name'));
    }

    public function test_le_filtre_ajax_par_categorie_ne_garde_que_la_categorie_choisie(): void
    {
        $citadines = $this->category(['slug' => 'citadines', 'name_fr' => 'Citadines', 'name_en' => 'City cars']);
        $luxe = $this->category([
            'slug' => 'luxe', 'name_fr' => 'Luxe', 'name_en' => 'Luxury', 'daily_price' => 150000,
        ]);

        $this->vehicle($citadines, ['brand' => 'Toyota', 'model' => 'Vitz']);
        $this->vehicle($luxe, ['brand' => 'Mercedes', 'model' => 'Classe E']);

        $donnees = json_decode(
            $this->post('/fr/vehicules/filtres', ['categorie' => 'citadines'])->assertOk()->getContent(),
            true,
        );

        $this->assertSame(1, $donnees['count']);
        $this->assertStringContainsString('Toyota Vitz', $donnees['html']);
        $this->assertStringNotContainsString('Mercedes Classe E', $donnees['html']);
    }

    public function test_le_tri_par_prix_decroissant_classe_les_plus_chers_d_abord(): void
    {
        $citadines = $this->category(['slug' => 'citadines', 'name_fr' => 'Citadines', 'name_en' => 'City cars']);
        $luxe = $this->category([
            'slug' => 'luxe', 'name_fr' => 'Luxe', 'name_en' => 'Luxury', 'daily_price' => 150000,
        ]);

        $this->vehicle($citadines, ['brand' => 'Toyota', 'model' => 'Vitz']);
        $this->vehicle($luxe, ['brand' => 'Mercedes', 'model' => 'Classe E']);

        $donnees = json_decode(
            $this->post('/fr/vehicules/filtres', ['tri' => 'prix-desc'])->assertOk()->getContent(),
            true,
        );

        $this->assertSame(2, $donnees['count']);
        $this->assertLessThan(
            strpos($donnees['html'], 'Toyota Vitz'),
            strpos($donnees['html'], 'Mercedes Classe E'),
        );
    }

    public function test_le_filtre_categorie_s_applique_au_chargement_de_la_page(): void
    {
        $citadines = $this->category(['slug' => 'citadines', 'name_fr' => 'Citadines', 'name_en' => 'City cars']);
        $luxe = $this->category([
            'slug' => 'luxe', 'name_fr' => 'Luxe', 'name_en' => 'Luxury', 'daily_price' => 150000,
        ]);

        $this->vehicle($citadines, ['brand' => 'Toyota', 'model' => 'Vitz']);
        $this->vehicle($luxe, ['brand' => 'Mercedes', 'model' => 'Classe E']);

        $this->get('/fr/vehicules?categorie=citadines')
            ->assertOk()
            ->assertSee('Toyota Vitz')
            ->assertDontSee('Mercedes Classe E');
    }

    public function test_la_page_d_une_categorie_ne_montre_que_ses_vehicules(): void
    {
        $citadines = $this->category(['slug' => 'citadines', 'name_fr' => 'Citadines', 'name_en' => 'City cars']);
        $luxe = $this->category([
            'slug' => 'luxe', 'name_fr' => 'Luxe', 'name_en' => 'Luxury', 'daily_price' => 150000,
        ]);

        $this->vehicle($citadines, ['brand' => 'Toyota', 'model' => 'Vitz']);
        $this->vehicle($luxe, ['brand' => 'Mercedes', 'model' => 'Classe E']);

        $this->get('/fr/vehicules/categorie/citadines')
            ->assertOk()
            ->assertSee('Toyota Vitz')
            ->assertDontSee('Mercedes Classe E');
    }

    public function test_le_formulaire_de_filtres_vise_l_endpoint_ajax_et_ouvre_le_panneau_mobile(): void
    {
        $this->vehicle();

        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertSee('data-filters-url="'.route('vehicles.filter', ['locale' => 'fr']).'"', escape: false)
            ->assertSee('data-filters-panel', escape: false);
    }

    public function test_un_filtre_sans_resultat_renvoie_l_etat_vide(): void
    {
        $this->vehicle(null, ['transmission' => 'manuelle']);

        $donnees = json_decode(
            $this->post('/fr/vehicules/filtres', ['transmission' => 'automatique'])->assertOk()->getContent(),
            true,
        );

        $this->assertSame(0, $donnees['count']);
        $this->assertStringContainsString(__('general.results_empty_title'), $donnees['html']);
    }

    public function test_la_flotte_sans_dates_annonce_la_periode_par_defaut(): void
    {
        $this->category();
        $this->vehicle();

        // Le client doit savoir sur quelles dates on lui répond, sinon il peut
        // croire que le prix affiché vaut pour n'importe quand.
        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertSee(__('general.vehicles_free'))
            ->assertSee(today()->addDay()->translatedFormat('D d M'));
    }

    public function test_une_categorie_sans_slug_connu_renvoie_404(): void
    {
        $this->get('/fr/vehicules/categorie/luxe')->assertNotFound();
    }

    public function test_la_page_d_une_categorie_existe_dans_les_deux_langues(): void
    {
        $category = $this->category(['name_fr' => 'Luxe', 'name_en' => 'Luxe', 'slug' => 'luxe']);

        $this->vehicle($category);

        $this->get('/fr/vehicules/categorie/'.$category->slug)->assertOk();
        $this->get('/en/vehicules/categorie/'.$category->slug)->assertOk();
    }

    /**
     * La racine renvoie le visiteur sur la version française, avec une
     * redirection, et non une page d'accueil décalée.
     */
    public function test_la_racine_redirige_vers_le_francais(): void
    {
        $this->get('/')->assertRedirect('/fr');
    }

    public function test_une_langue_inconnue_ne_serit_pas_interpretee_comme_un_chemin(): void
    {
        $this->get('/de/vehicules')->assertNotFound();
    }

    /**
     * Une voiture peut avoir quatre photos : la fiche doit les montrer
     * toutes, et pas seulement la photo principale.
     */
    public function test_la_fiche_vehicule_affiche_toute_la_galerie(): void
    {
        Storage::fake('fleet');
        $vehicle = $this->vehicle();

        foreach (['avant.jpg', 'arriere.jpg', 'interieur.jpg', 'cote.jpg'] as $photo) {
            $vehicle->addMedia(UploadedFile::fake()->image($photo))
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        }

        $response = $this->get('/fr/vehicules/'.$vehicle->id)->assertOk();
        $html = $response->getContent();

        $this->assertSame(4, substr_count($html, 'class="gallery-thumb '));
        $this->assertSame(4, substr_count($html, 'data-gallery-thumb='));

        foreach ($vehicle->fresh()->galleryPhotos() as $media) {
            $response->assertSee($media->getUrl(), escape: false);
        }
    }

    public function test_la_fiche_vehicule_affiche_sa_description_dans_la_langue_du_visiteur(): void
    {
        $vehicle = $this->vehicle(null, [
            'description_fr' => 'Une voiture confortable pour Lomé.',
            'description_en' => 'A comfortable car for Lomé.',
        ]);

        $this->get('/fr/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertSee('À propos de ce véhicule')
            ->assertSee('Une voiture confortable pour Lomé.');

        $this->get('/en/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertSee('About this vehicle')
            ->assertSee('A comfortable car for Lomé.');
    }

    public function test_une_description_anglaise_vide_retombe_sur_le_francais(): void
    {
        $vehicle = $this->vehicle(null, [
            'description_fr' => 'Texte français de secours.',
            'description_en' => null,
        ]);

        $this->get('/en/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertSee('Texte français de secours.');
    }

    public function test_les_images_defilent_toutes_les_cinq_secondes(): void
    {
        $vehicle = $this->vehicle();

        $this->get('/fr/vehicules/'.$vehicle->id)
            ->assertOk()
            ->assertSee('data-gallery-interval="5000"', escape: false);

        $this->get('/fr')
            ->assertOk()
            ->assertSee('data-hero-interval="5000"', escape: false);
    }

    public function test_la_fiche_vehicule_repond_200_avec_les_dates_de_recherche(): void
    {
        $vehicle = $this->vehicle();

        $this->get('/fr/vehicules/'.$vehicle->id.'?'.http_build_query([
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]))->assertOk();

        $this->get('/en/vehicules/'.$vehicle->id.'?'.http_build_query([
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]))->assertOk();
    }

    // ── Bascule de langue ────────────────────────────────────────────────────

    #[DataProvider('publicPages')]
    public function test_le_bouton_de_langue_mene_a_la_meme_page_dans_l_autre_langue(string $chemin): void
    {
        $this->get('/fr'.$chemin)
            ->assertOk()
            ->assertSee('href="'.url('en'.$chemin).'"', escape: false);

        $this->get('/en'.$chemin)
            ->assertOk()
            ->assertSee('href="'.url('fr'.$chemin).'"', escape: false);
    }

    public function test_la_bascule_de_langue_conserve_les_dates_de_recherche(): void
    {
        $query = '?du='.now()->addDay()->toDateString().'&au='.now()->addDays(5)->toDateString();

        $this->get('/fr/vehicules'.$query)->assertOk();

        // Les dates de recherche font partie de la page : sans elles, changer
        // de langue revient à la page d'accueil et perd la recherche du client.
        $this->get('/fr/vehicules'.$query)
            ->assertSee('href="'.url('/en/vehicules').'"', escape: false);
    }

    public function test_le_bouton_de_langue_de_l_accueil_ne_double_pas_le_prefixe(): void
    {
        // Régression : la bascule construisait « en/fr » et renvoyait 404.
        $response = $this->get('/fr')->assertOk();

        $response->assertDontSee('href="en/fr"', escape: false);
        $response->assertDontSee('href="en/en"', escape: false);
    }

    // ─│ Absence de clé de traduction visible ────────────────────────────────

    #[DataProvider('publicPages')]
    public function test_chaque_page_est_ecrite_entierement_dans_sa_langue(string $chemin): void
    {
        foreach (['fr', 'en'] as $locale) {
            $contenu = $this->get('/'.$locale.$chemin)->assertOk()->getContent();

            $this->assertSame(
                [],
                $this->clesNonTraduites($contenu),
                "Une clé de traduction brute est visible sur /{$locale}{$chemin}.",
            );
        }
    }

    public function test_les_pages_anglaises_sont_redigees_en_anglais(): void
    {
        $contenu = $this->get('/en/faq')->assertOk()->getContent();

        // Une page anglaise qui affiche du français n'est pas une page
        // traduite : c'est la moitié du site qui ment sur son contenu.
        $this->assertStringContainsString('Frequently asked questions', $contenu);
        $this->assertStringNotContainsString('Questions fréquentes', $contenu);
    }

    /**
     * Une clé de traduction non résolue s'affiche brute à l'écran
     * (« nav.filters »). On les repère sur le texte visible, pas sur le HTML,
     * pour éviter les faux positifs du JavaScript inline.
     *
     * Les adresses e-mail et les URL sont retirées avant l'analyse : leur
     * domaine contient lui aussi un point, et « autorent.tg » n'est pas une
     * clé de traduction.
     *
     * @return array<int, string>
     */
    protected function clesNonTraduites(string $html): array
    {
        $texte = html_entity_decode(strip_tags(
            preg_replace('#<(script|style)[^>]*>.*?</\1>#s', '', $html) ?? ''
        ));

        $texte = preg_replace('#\b[\w.+-]+@[\w.-]+\.\w{2,}\b#', ' ', $texte) ?? '';
        $texte = preg_replace('#\bhttps?://\S+#', ' ', $texte) ?? '';

        preg_match_all('/\b[a-z][a-z0-9_]*\.[a-z0-9_]{2,}\b/', $texte, $trouves);

        return array_values(array_unique($trouves[0]));
    }

    /** Le texte visible de la page, scripts et styles retirés. */
    protected function texteVisible(string $html): string
    {
        return html_entity_decode(strip_tags(
            preg_replace('#<(script|style)[^>]*>.*?</\1>#s', '', $html) ?? ''
        ));
    }
}
