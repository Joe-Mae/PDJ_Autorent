<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de réservation, envoyé en file d'attente.
 *
 * C'est le miroir écrit du message WhatsApp : mêmes faits, mise en page lisible
 * sur un écran et imprimable. Il part par la file (ShouldQueue) pour ne jamais
 * ralentir le tunnel — l'envoi réel se fait par un worker, loin de la requête
 * HTTP.
 *
 * Rien n'y est écrit en dur : l'identité visuelle (nom, couleurs, téléphone,
 * adresse, devise) sort du SettingsService, pour qu'un blanc-marque change de
 * marque sans toucher au code.
 */
class BookingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $subjectKey  clé de langue du sujet, dans notifications.subjects
     * @param  string  $introKey  clé de langue du paragraphe d'ouverture
     * @param  array<string, mixed>  $lines  lignes « libellé => valeur » du tableau
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly string $subjectKey,
        public readonly string $introKey,
        public readonly array $lines = [],
        public readonly string $footerKey = 'notifications.footers.agency',
    ) {
        $this->onQueue('mails');
    }

    public function envelope(): Envelope
    {
        $settings = app(SettingsService::class);

        return new Envelope(
            from: $settings->string('contact.email'),
            subject: __('notifications.subjects.'.$this->subjectKey, [
                'reference' => $this->booking->reference,
                'company' => $settings->string('agency.name'),
            ]),
        );
    }

    public function content(): Content
    {
        $settings = app(SettingsService::class);

        return new Content(
            markdown: 'mail.booking',
            with: [
                'booking' => $this->booking,
                'settings' => $settings,
                'link' => $this->bookingLink(),
                'intro' => __($this->introKey, [
                    'reference' => $this->booking->reference,
                    'company' => $settings->string('agency.name'),
                ]),
                'lines' => $this->lines,
                'footer' => __($this->footerKey, [
                    'company' => $settings->string('agency.name'),
                    'phone' => $settings->formattedPhone(),
                ]),
            ],
        );
    }

    /**
     * Lien de suivi : public pour une réservation sans compte, réservé au
     * personnel pour les réservations invitées protégées par un jeton — un
     * e-mail ne doit pas ouvrir l'accès à quelqu'un qui ne l'aurait pas déjà.
     */
    private function bookingLink(): string
    {
        $booking = $this->booking;

        if ($booking->guest_access_token_hash !== null) {
            return route('booking.confirmation', [
                'locale' => 'fr',
                'booking' => $booking,
            ]);
        }

        return app(NotificationService::class)->publicLink($booking);
    }
}
