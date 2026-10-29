<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\MailConfigurator;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail de réservation, envoyé immédiatement.
 *
 * C'est le miroir écrit du message WhatsApp : mêmes faits, mise en page lisible
 * sur un écran et imprimable. Aucun worker de file n'étant configuré, l'envoi
 * est synchrone — le délai SMTP est borné et NotificationService absorbe les
 * erreurs pour ne jamais ralentir ni casser le tunnel.
 *
 * Rien n'y est écrit en dur : l'identité visuelle (nom, couleurs, téléphone,
 * adresse, devise) sort du SettingsService, pour qu'un blanc-marque change de
 * marque sans toucher au code.
 */
class BookingMail extends Mailable
{
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
    ) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingsService::class);

        return new Envelope(
            from: app(MailConfigurator::class)->fromAddress(),
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
     * Lien de suivi : la fiche publique pour une réservation sans compte,
     * la recherche par référence pour une demande invitée.
     *
     * Un invité n'a que le jeton de son lien, jamais conservé en clair : la
     * page de recherche pré-remplie est le seul point d'entrée qui fonctionne
     * depuis n'importe quel appareil, avec la référence déjà dans l'e-mail.
     */
    private function bookingLink(): string
    {
        $booking = $this->booking;

        if ($booking->guest_access_token_hash !== null) {
            return route('booking.lookup', [
                'locale' => 'fr',
                'reference' => $booking->reference,
            ]);
        }

        return app(NotificationService::class)->publicLink($booking);
    }
}
