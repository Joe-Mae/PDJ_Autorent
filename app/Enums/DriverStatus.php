<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Actif = 'actif';
    case Indisponible = 'indisponible';
    case Conge = 'conge';

    public function isBookable(): bool
    {
        return $this === self::Actif;
    }

    public function label(): string
    {
        return trans("driver.status.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
