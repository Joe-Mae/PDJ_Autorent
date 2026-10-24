<?php

namespace App\Enums;

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

    /** Durée de conservation en mois après la fin de la location. 0 = pas de purge. */
    public function retentionMonths(): int
    {
        return match ($this) {
            self::IdentityCard => 60,
            self::DriverLicense => 60,
            self::Selfie, self::Contract, self::VehiclePhoto => 12,
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
