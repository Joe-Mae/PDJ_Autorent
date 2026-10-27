<?php

namespace App\Enums;

enum DriverOnlyBookingStatus: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case Refusee = 'refusee';
    case Annulee = 'annulee';

    public function isActive(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee], true);
    }

    public function label(): string
    {
        return trans("driver.solo_status.{$this->value}");
    }

    /** Teinte du badge, définie dans `app.scss` (bloc « Couleurs de statut »). */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'attente',
            self::Confirmee => 'confirmee',
            self::Refusee => 'refusee',
            self::Annulee => 'annulee',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
