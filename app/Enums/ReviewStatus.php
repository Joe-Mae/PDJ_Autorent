<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case EnAttente = 'en_attente';
    case Publiee = 'publiee';
    case Rejetee = 'rejetee';

    public function label(): string
    {
        return trans("review.status.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
