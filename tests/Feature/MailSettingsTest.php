<?php

namespace Tests\Feature;

use App\Mail\AgencyRequestMail;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Services\MailConfigurator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Configuration des e-mails et notifications de réservation.
 *
 * La page /admin/emails est le seul endroit où l'agence règle son serveur
 * SMTP : elle doit rester réservée au super-administrateur, enregistrer sans
 * jamais stocker le mot de passe en clair, et s'appliquer réellement à
 * l'exécution. Côté tunnel, chaque demande doit prévenir le client (avec sa
 * référence) et l'agence.
 */
class MailSettingsTest extends TestCase
{
    // ── Accès ────────────────────────────────────────────────────────────────

    public function test_le_super_admin_ouvre_la_page_des_e_mails(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->get(route('admin.mail.edit'))
            ->assertOk()
            ->assertSee(__('admin.mail_settings'))
            ->assertSee('name="host"', escape: false);
    }

    public function test_un_gestionnaire_ne_peut_pas_configurer_les_e_mails(): void
    {
        $this->actingAs($this->staff('gestionnaire'))
            ->get(route('admin.mail.edit'))
            ->assertForbidden();
    }

    public function test_un_agent_d_accueil_ne_peut_pas_configurer_les_e_mails(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get(route('admin.mail.edit'))
            ->assertForbidden();
    }

    // ── Enregistrement ───────────────────────────────────────────────────────

    public function test_le_super_admin_enregistre_les_reglages_smtp(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.mail.update'), $this->payload())
            ->assertRedirect(route('admin.mail.edit'))
            ->assertSessionHas('status');

        $this->assertTrue($this->settings()->bool('mail.enabled'));
        $this->assertSame('smtp.gmail.com', $this->settings()->string('mail.host'));
        $this->assertSame(587, $this->settings()->int('mail.port'));
        $this->assertSame('tls', $this->settings()->string('mail.encryption'));
        $this->assertSame('agence@gmail.com', $this->settings()->string('mail.username'));
        $this->assertSame('no-reply@pdj-autorent.tg', $this->settings()->string('mail.from_address'));
        $this->assertSame('PDJ-AUTORENT', $this->settings()->string('mail.from_name'));
        $this->assertSame('demandes@pdj-autorent.tg', $this->settings()->string('mail.notification_address'));
    }

    public function test_le_mot_de_passe_smtp_est_chiffre_en_base(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.mail.update'), $this->payload(['password' => 'mot-de-passe-smtp']))
            ->assertSessionHasNoErrors();

        $stored = $this->settings()->string('mail.password');

        $this->assertNotSame('mot-de-passe-smtp', $stored);
        $this->assertSame('mot-de-passe-smtp', Crypt::decryptString($stored));
        $this->assertSame('mot-de-passe-smtp', app(MailConfigurator::class)->password());
    }

    public function test_un_mot_de_passe_vide_conserve_celui_deja_enregistre(): void
    {
        $admin = $this->staff('super_admin');

        $this->actingAs($admin)
            ->post(route('admin.mail.update'), $this->payload(['password' => 'premier-secret']));

        $this->actingAs($admin)
            ->post(route('admin.mail.update'), $this->payload(['password' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('premier-secret', app(MailConfigurator::class)->password());
    }

    public function test_la_case_supprimer_efface_le_mot_de_passe(): void
    {
        $admin = $this->staff('super_admin');

        $this->actingAs($admin)
            ->post(route('admin.mail.update'), $this->payload(['password' => 'a-effacer']));

        $this->actingAs($admin)
            ->post(route('admin.mail.update'), $this->payload(['clear_password' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertFalse(app(MailConfigurator::class)->hasPassword());
    }

    public function test_l_adresse_d_expedition_est_obligatoire(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.mail.update'), $this->payload(['from_address' => '']))
            ->assertSessionHasErrors('from_address');

        $this->assertSame('', $this->settings()->string('mail.from_address'));
    }

    // ── Application à l'exécution ────────────────────────────────────────────

    public function test_les_reglages_enregistres_pilotent_la_configuration_mail(): void
    {
        $this->settings()->putMany([
            'mail.enabled' => '1',
            'mail.host' => 'smtp.example.com',
            'mail.port' => 465,
            'mail.encryption' => 'ssl',
            'mail.username' => 'no-reply@example.com',
            'mail.password' => app(MailConfigurator::class)->encryptPassword('secret'),
            'mail.from_address' => 'no-reply@example.com',
            'mail.from_name' => 'Agence Test',
        ]);

        app(MailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('secret', config('mail.mailers.smtp.password'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
        $this->assertSame('Agence Test', config('mail.from.name'));
    }

    public function test_sans_activation_la_configuration_du_fichier_reste_en_place(): void
    {
        $hostBefore = config('mail.mailers.smtp.host');

        $this->settings()->putMany([
            'mail.enabled' => '0',
            'mail.host' => 'smtp.example.com',
        ]);

        app(MailConfigurator::class)->apply();

        $this->assertSame($hostBefore, config('mail.mailers.smtp.host'));
    }

    public function test_l_expediteur_et_la_boite_de_reception_retombent_sur_le_contact(): void
    {
        $this->settings()->putMany([
            'contact.email' => 'agence@example.com',
            'mail.from_address' => '',
            'mail.notification_address' => '',
        ]);

        $mail = app(MailConfigurator::class);

        $this->assertSame('agence@example.com', $mail->fromAddress());
        $this->assertSame('agence@example.com', $mail->notificationAddress());
        $this->assertSame($this->settings()->string('agency.name'), $mail->fromName());
    }

    // ── Envoi de test ────────────────────────────────────────────────────────

    public function test_l_envoi_de_test_part_vers_l_adresse_demandee(): void
    {
        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.mail.test'), ['email' => 'verif@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertSame(
            'verif@example.com',
            $messages[0]->getEnvelope()->getRecipients()[0]->getAddress(),
        );
    }

    public function test_un_echec_smtp_est_affiche_sans_casser_la_page(): void
    {
        Mail::shouldReceive('forgetMailers')->once();
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('Connexion refusée'));

        $this->actingAs($this->staff('super_admin'))
            ->post(route('admin.mail.test'), ['email' => 'verif@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ── Notifications à la création ──────────────────────────────────────────

    public function test_une_demande_invitee_notifie_le_client_et_l_agence(): void
    {
        Mail::fake();

        $vehicle = $this->vehicle();
        $startsAt = now()->addDay();

        $this->post(route('booking.guest.store', ['locale' => 'fr', 'vehicle' => $vehicle]), [
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDays(4)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
        ])->assertRedirect();

        $booking = Booking::query()->sole();

        Mail::assertSent(BookingMail::class, function (BookingMail $mail) use ($booking): bool {
            return $mail->hasTo('afi@example.com') && $mail->booking->is($booking);
        });

        Mail::assertSent(AgencyRequestMail::class, function (AgencyRequestMail $mail) use ($booking): bool {
            return $mail->hasTo('contact@pdj-autorent.tg') && $mail->reference === $booking->reference;
        });
    }

    public function test_l_accuse_de_reception_invite_renvoie_vers_la_recherche_par_reference(): void
    {
        Mail::fake();

        $vehicle = $this->vehicle();
        $startsAt = now()->addDay();

        $this->post(route('booking.guest.store', ['locale' => 'fr', 'vehicle' => $vehicle]), [
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDays(4)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
        ])->assertRedirect();

        $booking = Booking::query()->sole();

        Mail::assertSent(BookingMail::class, function (BookingMail $mail) use ($booking): bool {
            $html = $mail->render();

            return str_contains($html, 'consulter-reservation')
                && str_contains($html, $booking->reference);
        });
    }

    public function test_une_demande_de_chauffeur_seul_alerte_l_agence(): void
    {
        Mail::fake();
        Storage::fake('documents');
        $this->driver();

        $startsAt = now()->addDays(2);

        $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDay()->toDateString(),
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect();

        Mail::assertSent(AgencyRequestMail::class, function (AgencyRequestMail $mail): bool {
            return $mail->hasTo('contact@pdj-autorent.tg')
                && $mail->subjectKey === 'new_driver_only_request';
        });

        Mail::assertNotSent(BookingMail::class);
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'enabled' => '1',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'agence@gmail.com',
            'from_address' => 'no-reply@pdj-autorent.tg',
            'from_name' => 'PDJ-AUTORENT',
            'notification_address' => 'demandes@pdj-autorent.tg',
            ...$overrides,
        ];
    }
}
