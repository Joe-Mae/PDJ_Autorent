<?php

namespace App\Enums;

/**
 * Un document n'est jamais validé automatiquement : un agent décide.
 * C'est la seule garantie que le client qui vient à l'agence a des vrais papiers.
 */
enum DocumentStatus: string
{
    case EnAttente = 'en_attente';
    case Conforme = 'conforme';
    case NonConforme = 'non_conforme';
    case ARelever = 'a_relever';
    case Presente = 'presente';
    case Expire = 'expire';

    /** Le document peut être réutilisé sur une nouvelle réservation. */
    public function isReusable(): bool
    {
        return $this === self::Conforme;
    }

    public function label(): string
    {
        return trans("document.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Conforme, self::Presente => 'success',
            self::NonConforme, self::Expire => 'danger',
            self::ARelever => 'secondary',
        };
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
