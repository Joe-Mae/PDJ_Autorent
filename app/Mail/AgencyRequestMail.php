<?php

namespace App\Mail;

use App\Services\MailConfigurator;
use App\Services\SettingsService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail interne : une demande vient d'arriver.
 *
 * Il part vers la boîte de l'agence, pas vers le client. C'est le pendant du
 * mail de prise en compte : l'un rassure le client avec sa référence, l'autre
 * prévient l'équipe qu'il y a quelque chose à confirmer.
 *
 * Le contenu est passé en lignes libellé => valeur pour servir aussi bien une
 * location qu'une demande de chauffeur seul, sans multiplier les classes.
 */
class AgencyRequestMail extends Mailable
{
    /**
     * @param  array<string, string>  $lines  lignes « libellé => valeur »
     * @param  string  $subjectKey  clé de langue du sujet, dans notifications.subjects
     */
    public function __construct(
        public readonly string $reference,
        public readonly array $lines,
        public readonly string $url,
        public readonly string $subjectKey = 'new_request',
        public readonly string $introKey = 'new_request',
    ) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingsService::class);
        $mail = app(MailConfigurator::class);

        return new Envelope(
            from: $mail->fromAddress(),
            subject: __('notifications.subjects.'.$this->subjectKey, [
                'reference' => $this->reference,
                'company' => $settings->string('agency.name'),
            ]),
        );
    }

    public function content(): Content
    {
        $settings = app(SettingsService::class);

        return new Content(
            markdown: 'mail.agency-request',
            with: [
                'reference' => $this->reference,
                'lines' => $this->lines,
                'url' => $this->url,
                'settings' => $settings,
                'intro' => __('notifications.intros.'.$this->introKey, [
                    'reference' => $this->reference,
                    'company' => $settings->string('agency.name'),
                ]),
            ],
        );
    }
}
