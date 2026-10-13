<?php

namespace App\Enums;

enum DocumentType: string
{
    case IdentityCard = 'piece_identite';
    case DriverLicense = 'permis';
    case Selfie = 'selfie';
    case Contract = 'contrat';
    case VehiclePhoto = 'photo_vehicule';

    /** Document que le client doit fournir avant le retrait, selon qu'il est chauffeur ou non. */
    public static function requiredForClient(bool $withDriver): array
    {
        return $withDriver
            ? [self::IdentityCard]
            : [self::IdentityCard, self::DriverLicense];
    }

    public function isRequiredOnline(): bool
    {
        return in_array($this, [self::IdentityCard, self::DriverLicense, self::Selfie], true);
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
