<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La déconnexion du client.
 *
 * Sur un téléphone partagé — au guichet de l'agence comme à la maison — un même
 * appareil sert souvent à deux personnes. Sans commande de déconnexion visible
 * dans « Mon espace », le second client se retrouve dans le compte du premier,
 * avec ses réservations et ses documents sous les yeux.
 *
 * Ces tests verrouillent trois choses : la commande est présente sur toutes les
 * pages de l'espace client, elle est bien un POST protégé par un jeton (un GET
 * permettrait à un tiers de déconnecter le client par une simple image glissée dans
 * une page), et elle ferme réellement la session.
 */
class AccountLogoutTest extends TestCase
{
    /** Les pages de l'espace client qui doivent porter la barre de session. */
    public static function pagesDeLEspaceClient(): array
    {
        return [
            'tableau de bord' => ['/fr/mon-espace'],
            'réservations' => ['/fr/mon-espace/reservations'],
            'documents' => ['/fr/mon-espace/documents'],
            'profil' => ['/fr/mon-espace/profil'],
        ];
    }

    /** Des pages où le client atterrit sans passer par « Mon espace ». */
    public static function pagesPubliques(): array
    {
        return [
            'accueil' => ['/fr'],
            'flotte' => ['/fr/vehicules'],
            'connexion' => ['/fr/connexion'],
            'questions' => ['/fr/faq'],
        ];
    }

    // ── La commande est là ───────────────────────────────────────────────────

    #[DataProvider('pagesDeLEspaceClient')]
    public function test_la_deconnexion_est_proposee_sur_toute_page_de_l_espace_client(string $url): void
    {
        $html = $this->actingAs($this->customer())->get($url)->assertOk()->getContent();

        $this->assertStringContainsString(
            route('logout', ['locale' => 'fr']),
            $html,
            "La commande de déconnexion manque sur {$url}.",
        );
    }

    #[DataProvider('pagesDeLEspaceClient')]
    public function test_la_deconnexion_est_un_post_protige_par_un_jeton(string $url): void
    {
        $html = $this->actingAs($this->customer())->get($url)->assertOk()->getContent();

        // Un lien GET serait une faille : n'importe quelle page tierce pourrait
        // 嵌入式 une image qui déconnecte le client. On exige un formulaire POST
        // carryant le jeton de session.
        $this->assertMatchesRegularExpression(
            '/<form[^>]*action="'.preg_quote(route('logout', ['locale' => 'fr']), '/').'"[^>]*method="POST"/s',
            $html,
            'Le formulaire de déconnexion n\'est pas un POST.',
        );

        $formulaire = $this->formulaireDeDeconnexion($html);

        $this->assertStringContainsString('_token', $formulaire, 'Le jeton anti-CSRF est absent.');

        // Et surtout pas de lien GET vers la déconnexion.
        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="'.preg_quote(route('logout', ['locale' => 'fr']), '/').'"/',
            $html,
            'Un lien GET vers la déconnexion traîne encore quelque part.',
        );
    }

    #[DataProvider('pagesDeLEspaceClient')]
    public function test_un_visiteur_non_connecte_ne_voit_pas_la_barre_de_session(string $url): void
    {
        // La redirection vers la connexion suffit : l'invité n'atteint jamais le
        // gabarit qui porte la barre. Tester le corps d'une redirection ne
        // prouverait rien, puisque cette réponse n'a pas de corps.
        $this->get($url)->assertRedirect(route('login', ['locale' => 'fr']));
    }

    #[DataProvider('pagesPubliques')]
    public function test_un_visiteur_non_connecte_ne_voit_aucune_deconnexion(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringNotContainsString(
            route('logout', ['locale' => 'fr']),
            $html,
            "Une déconnexion est proposée à un visiteur non connecté sur {$url}.",
        );
    }

    // ── La commande fonctionne ──────────────────────────────────────────────

    public function test_se_deconnecter_ferme_la_session_et_rend_la_main_a_l_accueil(): void
    {
        $client = $this->customer();

        $this->actingAs($client)->get('/fr/mon-espace')->assertOk();

        $this->post('/fr/deconnexion')
            ->assertRedirect(route('home', ['locale' => 'fr']));

        $this->assertGuest();
    }

    public function test_apres_deconnexion_l_espace_client_est_de_nouveau_refuse(): void
    {
        $client = $this->customer();

        $this->actingAs($client)->post('/fr/deconnexion')->assertRedirect();

        // Sans session, l'espace client renvoie vers la connexion.
        $this->get('/fr/mon-espace')
            ->assertRedirect(route('login', ['locale' => 'fr']));
    }

    public function test_on_peut_se_connecter_avec_un_autre_compte_apres_avoir_deconnecte_le_premier(): void
    {
        $premier = $this->customer(['email' => 'premier@exemple.tg']);
        $second = $this->customer(['email' => 'second@exemple.tg']);

        $this->actingAs($premier)->post('/fr/deconnexion')->assertRedirect();

        $this->post('/fr/connexion', [
            'login' => 'second@exemple.tg',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($second);

        // Le premier compte n'a plus de session : plus personne ne voit ses
        // réservations par-dessus l'épaule du second.
        $this->actingAs($second)
            ->get('/fr/mon-espace/reservations')
            ->assertOk()
            ->assertDontSee($premier->first_name);
    }

    public function test_la_deconnexion_du_client_ne_touche_pas_aux_comptes_du_personnel(): void
    {
        $client = $this->customer();
        $agent = $this->staff('agent_accueil');

        $this->actingAs($client)->post('/fr/deconnexion')->assertRedirect();

        $this->assertGuest();

        // Le compte du personnel reste utilisable : la déconnexion ne purge que
        // la session courante.
        $this->actingAs($agent)->get('/admin')->assertOk();
    }

    public function test_le_langue_est_conserve_a_la_deconnexion(): void
    {
        $client = $this->customer();

        // Un client anglophone doit revenir sur la version anglaise, pas être
        // renvoyé d'un coup sur la page d'accueil française.
        $this->actingAs($client)
            ->post('/en/deconnexion')
            ->assertRedirect(route('home', ['locale' => 'en']));
    }

    public function test_le_commandement_du_personnel_garde_sa_propre_deconnexion(): void
    {
        // Le panneau d'administration a toujours eu sa déconnexion : la barre du
        // client ne doit pas l'avoir fait disparaître par effet de bord.
        $html = $this->actingAs($this->staff('super_admin'))->get('/admin')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]*action="'.preg_quote(route('logout', ['locale' => 'fr']), '/').'"[^>]*method="POST"/s',
            $html,
        );
    }

    public function test_pas_de_second_bouton_de_deconnexion_dans_le_contenu_de_la_page(): void
    {
        $html = $this->actingAs($this->customer())->get('/fr/mon-espace/profil')->assertOk()->getContent();

        $deconnexion = route('logout', ['locale' => 'fr']);

        // Trois accès sont voulus : l'icône de l'en-tête (présente partout), le
        // bouton du menu mobile (téléphone), et le bouton libellé de la barre de
        // l'espace client. Aucun n'est visible en même temps que les autres sur
        // un même écran. La page Profil en avait un quatrième, tout en bas, que
        // personne ne trouve plus.
        //
        // On découpe la page sur `<main>` plutôt que de retirer les blocs à coups
        // d'expressions régulières : deux « boutons » qui se suivent dans le code
        // n'ont pas à être隣 — seule compte leur position dans le document.
        [$avant, $corps] = array_pad(explode('<main>', $html, 2), 2, '');

        $this->assertSame(2, substr_count($avant, $deconnexion), 'En-tête et menu mobile doivent porter la déconnexion.');
        $this->assertSame(1, substr_count($corps, $deconnexion), 'Le contenu de la page doit n\'en porter qu\'une.');

        // Et cette unique occurrence vient bien de la barre, pas d'un bouton
        // oublié en bas de page.
        $this->assertMatchesRegularExpression(
            '#<div class="account-bar\b[^>]*>(?:(?!</main>).)*'.preg_quote($deconnexion, '#').'#s',
            $corps,
            'La déconnexion du contenu doit venir de la barre de session.',
        );
    }

    #[DataProvider('pagesPubliques')]
    public function test_un_client_peut_se_deconnecter_de_n_importe_quelle_page(string $url): void
    {
        // Le cas réel du téléphone partagé : le client suivant arrive sur la
        // page d'accueil, pas sur « Mon espace ». Il doit y trouver la commande.
        $html = $this->actingAs($this->customer())->get($url)->assertOk()->getContent();

        $this->assertStringContainsString(
            route('logout', ['locale' => 'fr']),
            $html,
            "Aucune déconnexion sur {$url} : le client ne peut pas reprendre la main.",
        );
    }

    // ── Outils ───────────────────────────────────────────────────────────────

    /** Extrait le corps du formulaire de déconnexion. */
    private function formulaireDeDeconnexion(string $html): string
    {
        preg_match(
            '/<form[^>]*action="'.preg_quote(route('logout', ['locale' => 'fr']), '/').'"[^>]*method="POST"[^>]*>(.*?)<\/form>/s',
            $html,
            $corps,
        );

        return $corps[1] ?? '';
    }

    public function test_la_route_de_deconnexion_n_accepte_pas_le_get(): void
    {
        // Dernière ligne de défense : même si une vue réintroduisait un lien, la
        // route refuserait le GET.
        $this->get('/fr/deconnexion')->assertStatus(405);
    }

    public function test_le_jeton_du_formulaire_est_bien_celui_de_la_session(): void
    {
        // Le refus du jeton falsifié (419) n'est pas testable ici : Laravel
        // court-circuite la vérification dès que l'application tourne en
        // console, pour ne pas contraindre chaque test à fabriquer un jeton.
        // Ce qui nous revient de vérifier — et qui compte — est que le champ
        // `_token` du formulaire porte bien la valeur de la session du client,
        // et non un champ décoratif laissé vide par le gabarit.
        $client = $this->customer();

        $html = $this->actingAs($client)->get('/fr/mon-espace')->assertOk()->getContent();

        preg_match(
            '#<form[^>]*action="'.preg_quote(route('logout', ['locale' => 'fr']), '/').'".*?name="_token" value="([^"]+)"#s',
            $html,
            $corps,
        );

        $this->assertNotEmpty(
            $corps,
            'Le formulaire de déconnexion ne contient aucun jeton.',
        );

        $this->assertSame(
            session()->token(),
            $corps[1],
            'Le jeton du formulaire ne correspond pas à la session : le formulaire ne protège rien.',
        );

        $this->assertNotSame('', trim($corps[1]));
    }

    public function test_un_client_ne_peut_pas_fermer_la_session_d_un_autre(): void
    {
        $autre = $this->customer();

        // La route ne prend aucun paramètre : un client ne peut viser que sa
        // propre session, et les données envoyées en trop sont ignorées.
        $this->actingAs($this->customer())
            ->post('/fr/deconnexion', ['user_id' => $autre->id])
            ->assertRedirect();

        $this->assertGuest();

        // Le compte visé reste utilisable : rien n'a été touché à son profil.
        $this->actingAs($autre)
            ->get('/fr/mon-espace')
            ->assertOk()
            ->assertSee($autre->first_name);
    }
}
