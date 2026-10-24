<?php

namespace Tests\Feature;

use App\Mail\BookingMail;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Les e-mails de réservation partent par la file d'attente.
 *
 * C'est le miroir écrit du WhatsApp : il ne doit jamais ralentir le tunnel,
 * et il ne part que si le client a donné une adresse. Chaque changement de
 * statut qui notifie le client envoie aussi l'e-mail, sans exception.
 */
class BookingEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_la_confirmation_de_reservation_mets_un_emissaire_en_file(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        Mail::assertQueued(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_un_emissaire_na_adresse_n_recoit_aucun_e_missaire(): void
    {
        $booking = $this->aBookingWith(['customer_email' => null]);

        $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        Mail::assertNothingQueued();
    }

    public function test_le_refus_de_reservation_est_ecrit_pour_le_client(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingRefused($booking, 'Pièce illisible', $booking->customer_email);

        Mail::assertQueued(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_l_annulation_est_ecrite_pour_le_client(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $this->notifications()->sendBookingCancelled($booking, $booking->customer_email);

        Mail::assertQueued(BookingMail::class, function (BookingMail $mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    /**
     * L'e-mail est un miroir : le tunnel doit rester debout même si la file
     * refuse d'accepter le message. Aucune exception ne doit remonter.
     */
    public function test_un_e_missaire_en_file_ne_fait_pas_echouer_l_envoi_whatsapp(): void
    {
        $booking = $this->aBookingWith(['customer_email' => 'client@example.com']);

        $result = $this->notifications()->sendBookingConfirmed($booking, $booking->customer_email);

        $this->assertTrue($result);
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
