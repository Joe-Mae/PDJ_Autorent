<?php

namespace Tests\Feature;

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
            'inscription' => ['/inscription'],
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

    public function test_homepage_renders_an_accessible_photo_carousel_from_vehicle_gallery(): void
    {
        Storage::fake('fleet');

        foreach (['hero-one.jpg', 'hero-two.jpg'] as $photo) {
            $vehicle = $this->vehicle();
            $vehicle->addMedia(UploadedFile::fake()->image($photo))
                ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');
        }

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
            ->assertSee('class="public-site structured-pages"', escape: false)
            ->assertSee('page-content', escape: false)
            ->assertSee('container-fluid', escape: false)
            ->assertSee('Questions fréquentes');
    }

    public function test_les_pages_flotte_et_espace_client_conservent_leur_mise_en_page(): void
    {
        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertDontSee('class="structured-pages"', escape: false);

        $this->actingAs($this->customer())
            ->get('/fr/mon-espace')
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

    public function test_la_flotte_sans_dates_annonce_la_periode_par_defaut(): void
    {
        $this->category();
        $this->vehicle();

        // Le client doit savoir sur quelles dates on lui répond, sinon il peut
        // croire que le prix affiché vaut pour n'importe quand.
        $this->get('/fr/vehicules')
            ->assertOk()
            ->assertSee('libre(s) sur vos dates');
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
