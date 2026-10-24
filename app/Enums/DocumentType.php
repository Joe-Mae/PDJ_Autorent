<?php

namespace App\Enums;

use App\Services\SettingsService;

enum DocumentType: string
{
    case IdentityCard = 'piece_identite';
    case DriverLicense = 'permis';
    case Selfie = 'selfie';
    case Contract = 'contrat';
    case VehiclePhoto = 'photo_vehicule';

    /** Documents que le client fournit en ligne pendant la réservation. */
    public static function requiredForSubmission(): array
    {
        return [self::IdentityCard];
    }

    /** Types that may be uploaded online; the driving licence is checked at pickup. */
    public static function acceptedOnline(): array
    {
        return [self::IdentityCard, self::Selfie];
    }

    /** Originaux vérifiés par le personnel avant la remise des clés. */
    public static function requiredAtPickup(bool $withDriver): array
    {
        return $withDriver
            ? [self::IdentityCard]
            : [self::IdentityCard, self::DriverLicense];
    }

    public function isAcceptedOnline(): bool
    {
        return in_array($this, self::acceptedOnline(), true);
    }

    /**
     * Durée de conservation en mois après la fin de la location. 0 = pas de purge.
     *
     * Les pièces d'état civil suivent une durée légale distincte des autres
     * documents, d'où une clé de réglage à part : les confondre ferait purger
     * un permis aussi vite qu'un selfie.
     */
    public function retentionMonths(): int
    {
        $settings = app(SettingsService::class);

        return match ($this) {
            self::IdentityCard, self::DriverLicense => $settings->int('document.identity_retention_months'),
            default => $settings->int('document.retention_months'),
        };
    }

    public function label(): string
    {
        return trans("document.type.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
