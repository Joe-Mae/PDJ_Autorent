<?php

namespace Tests\Feature;

use App\Mail\BookingMail;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Les e-mails de réservation partent immédiatement.
 *
 * C'est le miroir écrit du WhatsApp : il ne part que si le client a donné une
 * adresse, et il ne doit jamais ralentir le tunnel. L'envoi est synchrone —
 * aucun worker de file n'est configuré, un message « en file » n'arriverait
 * jamais. Chaque changement de statut qui notifie le client envoie l'e-mail.
 */
class BookingEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_la_confirmation_de_reservation_est_envoyee_au_client(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        Mail::assertSent(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_un_client_sans_adresse_ne_recoit_aucun_e_mail(): void
    {
        $booking = $this->aBookingWith(['customer_email' => null]);

        $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        Mail::assertNothingSent();
    }

    public function test_le_refus_de_reservation_est_ecrit_pour_le_client(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingRefused($booking, 'Pièce illisible', $booking->customer_email);

        Mail::assertSent(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_l_annulation_est_ecrite_pour_le_client(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingCancelled($booking, $booking->customer_email);

        Mail::assertSent(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    /**
     * L'e-mail est un miroir : le tunnel doit rester debout même si le serveur
     * SMTP refuse le message. L'échec est journalisé, aucune exception ne
     * remonte jusqu'à la réservation.
     */
    public function test_une_panne_smtp_ne_fait_pas_echouer_la_reservation(): void
    {
        Log::spy();

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP injoignable'));

        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $result = $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        $this->assertTrue($result);
        Log::shouldHaveReceived('error')->once();
    }

    private function aBookingWith(array $attributes): Booking
    {
        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(),
            'user' => $this->customer(),
            'with_driver' => true,
            'driver' => $this->driver(),
        ]);

        $booking->forceFill($attributes)->save();

        return $booking;
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }
}
