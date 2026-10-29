<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Réglages SMTP saisis par le super-administrateur.
 *
 * Laravel lit sa configuration mail depuis config/mail.php au démarrage. Une
 * agence qui change de boîte e-mail ne peut pas éditer un fichier .env : ce
 * service réécrit la configuration à l'exécution à partir des réglages
 * enregistrés en base, avant qu'aucun mailer ne soit construit.
 *
 * Le mot de passe est chiffré en base : il ne doit jamais apparaître en clair
 * dans une sauvegarde de la table settings.
 */
class MailConfigurator
{
    /** Délai d'attente SMTP : un serveur muet ne doit pas bloquer une requête. */
    private const TIMEOUT_SECONDS = 10;

    public function __construct(private readonly SettingsService $settings) {}

    /** Applique les réglages enregistrés. Sans hôte, on ne touche à rien. */
    public function apply(): void
    {
        if (! $this->settings->bool('mail.enabled')) {
            return;
        }

        $host = trim($this->settings->string('mail.host'));

        if ($host === '') {
            return;
        }

        $encryption = $this->encryption();
        $port = $this->settings->int('mail.port') ?: $this->defaultPort($encryption);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => trim($this->settings->string('mail.username')) ?: null,
            'mail.mailers.smtp.password' => $this->password(),
            // « none » coupe STARTTLS ; sinon Symfony négocie TLS dès que le
            // serveur le propose (ports 587 et 465).
            'mail.mailers.smtp.auto_tls' => $encryption !== 'none',
            'mail.mailers.smtp.timeout' => self::TIMEOUT_SECONDS,
            'mail.from.address' => $this->fromAddress(),
            'mail.from.name' => $this->fromName(),
        ]);
    }

    /** Adresse d'expédition : celle de la page e-mails, sinon le contact. */
    public function fromAddress(): string
    {
        $configured = trim($this->settings->string('mail.from_address'));

        if ($configured !== '') {
            return $configured;
        }

        $contact = trim($this->settings->string('contact.email'));

        return $contact !== '' ? $contact : (string) config('mail.from.address');
    }

    public function fromName(): string
    {
        $configured = trim($this->settings->string('mail.from_name'));

        return $configured !== '' ? $configured : $this->settings->string('agency.name');
    }

    /**
     * Boîte qui reçoit les demandes de réservation.
     *
     * Par défaut, l'e-mail de contact de l'agence : une seule adresse à changer
     * quand l'entreprise déménage sa boîte, sans toucher aux autres réglages.
     */
    public function notificationAddress(): string
    {
        $configured = trim($this->settings->string('mail.notification_address'));

        return $configured !== '' ? $configured : trim($this->settings->string('contact.email'));
    }

    /** L'envoi est-il réellement configuré ? */
    public function isConfigured(): bool
    {
        return $this->settings->bool('mail.enabled')
            && trim($this->settings->string('mail.host')) !== ''
            && $this->fromAddress() !== '';
    }

    public function hasPassword(): bool
    {
        return trim($this->settings->string('mail.password')) !== '';
    }

    /** Chiffre le mot de passe avant de l'écrire en base. */
    public function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    /** Mot de passe en clair pour le transport ; vide s'il est illisible. */
    public function password(): string
    {
        $stored = $this->settings->string('mail.password');

        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            // Ancienne valeur en clair (ou clé APP_KEY changée) : on ne casse
            // pas l'envoi, le super-admin ressaisira le mot de passe.
            return '';
        }
    }

    public function encryption(): string
    {
        $value = $this->settings->string('mail.encryption');

        return in_array($value, ['tls', 'ssl', 'none'], true) ? $value : 'tls';
    }

    private function defaultPort(string $encryption): int
    {
        return match ($encryption) {
            'ssl' => 465,
            'none' => 25,
            default => 587,
        };
    }
}
