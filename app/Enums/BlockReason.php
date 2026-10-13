<?php

namespace App\Enums;

enum BlockReason: string
{
    case Maintenance = 'maintenance';
    case ControlTechnique = 'controle_technique';
    case Accident = 'accident';
    case Detente = 'detente';
    case Interne = 'interne';

    /** Un blocage de détente est du confort, pas de l'indisponibilité : il priorise moins. */
    public function isHardBlock(): bool
    {
        return $this !== self::Detente;
    }

    public function label(): string
    {
        return trans("booking.block_reason.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
