<?php

namespace Tests\Feature;

use App\Models\Booking;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * L'accès au back-office et aux écrans agent.
 *
 * Ces pages montrent des données personnelles : nom, téléphone, documents
 * d'identité, montants. Un compte client qui y entre par une URL devinée
 *lit tout le parc et tout l'historique. La barrière est EnsureStaff, adossé
 * au rôle ; ces tests prouvent qu'elle tient pour chaque rôle.
 */
class BackOfficeAccessTest extends TestCase
{
    /** Écrans qui doivent rester hors de portée d'un compte client. */
    public static function backOfficeScreens(): array
    {
        return [
            'tableau de bord' => ['/admin'],
            'réservations' => ['/admin/reservations'],
            'documents à vérifier' => ['/admin/documents'],
            'véhicules' => ['/admin/vehicules'],
            'catégories' => ['/admin/categories'],
            'planning' => ['/admin/calendrier'],
            'chauffeurs' => ['/admin/chauffeurs'],
            'clients' => ['/admin/clients'],
            'paramètres' => ['/admin/parametres'],
            'écran de retrait' => ['/agent/retrait'],
            'écran de retour' => ['/agent/retour'],
            'caisse' => ['/agent/caisse'],
        ];
    }

    #[DataProvider('backOfficeScreens')]
    public function test_un_visiteur_non_connecte_est_renvoye_vers_la_connexion(string $url): void
    {
        $this->get($url)->assertRedirect(route('login', ['locale' => 'fr']));
    }

    /**
     * Régression : la redirection vers la connexion se construisait sans la
     * langue, et la génération de l'URL échouait. Un employé déconnecté qui
     * ouvrait /admin obtenait une erreur 500, pas le formulaire.
     */
    #[DataProvider('backOfficeScreens')]
    public function test_un_visiteur_non_connecte_recoit_le_formulaire_et_pas_une_erreur(string $url): void
    {
        $this->get($url)->assertRedirect(route('login', ['locale' => 'fr']));

        $this->followingRedirects()
            ->get($url)
            ->assertOk()
            ->assertSee(__('nav.login'), escape: false)
            ->assertSee('name="login"', escape: false);
    }

    #[DataProvider('backOfficeScreens')]
    public function test_un_compte_client_est_refuse(string $url): void
    {
        $this->actingAs($this->customer())->get($url)->assertForbidden();
    }

    /**
     * Un employé atteint tous les écrans de l'administration.
     *
     * On se place au rôle le plus large : depuis la mise en place des
     * périmètres, un agent d'accueil est légitimement refusé sur le catalogue
     * et les paramètres. La question « employé ou client ? » reste testée ici ;
     * la question « quel employé ? » est portée par StaffTierTest.
     */
    #[DataProvider('backOfficeScreens')]
    public function test_un_employe_peut_atteindre_son_ecran(string $url): void
    {
        $this->actingAs($this->staff('super_admin'))->get($url)->assertOk();
    }

    public function test_les_trois_roles_du_personnel_ont_acces_au_back_office(): void
    {
        foreach (['super_admin', 'gestionnaire', 'agent_accueil'] as $role) {
            $this->actingAs($this->staff($role))
                ->get('/admin')
                ->assertOk();
        }
    }

    public function test_un_compte_sans_aucun_role_est_refuse(): void
    {
        // Un compte créé mais jamais pourvu d'un rôle ne doit pas être pris
        // pour un employé : le rôle manquant vaut refus.
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();
    }

    public function test_un_client_ne_peut_pas_confirmer_une_reservation_par_l_url(): void
    {
        $booking = $this->pendingBooking();

        $this->actingAs($this->customer())
            ->post('/admin/reservations/'.$booking->reference.'/confirmer')
            ->assertForbidden();

        $this->assertSame(
            'en_attente',
            $booking->refresh()->status->value,
            'La réservation ne doit pas avoir changé de statut.',
        );
    }

    public function test_un_client_ne_peut_pas_lire_un_document_d_identite_d_autre(): void
    {
        $booking = $this->pendingBooking();

        $this->actingAs($this->customer())
            ->get('/admin/documents')
            ->assertForbidden();

        $this->assertNotNull($booking);
    }

    public function test_le_ressources_ne_pas_dependre_de_l_adresse_de_la_requete(): void
    {
        // Un lien d'adminforge ne doit pas donner accès au back-office.
        $this->actingAs($this->customer())
            ->get('/admin/vehicules?redirect=/admin/parametres')
            ->assertForbidden();
    }

    // ── Authentification ─────────────────────────────────────────────────────

    public function test_une_connexion_avec_le_bon_telephone_et_le_bon_mot_de_passe_reussit(): void
    {
        $staff = $this->staff('agent_accueil');

        $this->post('/fr/connexion', [
            'login' => $staff->phone,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_une_connexion_avec_un_mot_de_passe_faux_echoue(): void
    {
        $staff = $this->staff('agent_accueil');

        $this->post('/fr/connexion', [
            'login' => $staff->phone,
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_une_connexion_avec_un_numero_inconnu_echoue(): void
    {
        $this->post('/fr/connexion', [
            'login' => '22890000001',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    /**
     * Il n'y a pas d'espace client : un compte sans rôle du personnel ne peut
     * pas se connecter, et le refus est indistinguable d'un mot de passe
     * faux pour ne pas révéler qu'une fiche existe.
     */
    public function test_un_compte_sans_role_ne_peut_pas_se_connecter(): void
    {
        $customer = $this->customer();

        $this->post('/fr/connexion', [
            'login' => $customer->phone,
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_l_adresse_email_sert_egalement_a_se_connecter(): void
    {
        $staff = $this->staff('gestionnaire');

        $this->post('/fr/connexion', [
            'login' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_se_deconnecter_ferme_la_session(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->post('/fr/deconnexion')
            ->assertRedirect();

        $this->assertGuest();
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    protected function pendingBooking(): Booking
    {
        return $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(4),
            'user' => $this->customer(),
            'with_driver' => false,
        ]);
    }
}
