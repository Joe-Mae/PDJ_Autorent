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

    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Confirmee => 'success',
            self::Refusee => 'danger',
            self::Annulee => 'secondary',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
