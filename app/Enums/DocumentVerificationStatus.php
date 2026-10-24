<?php

namespace App\Enums;

enum DocumentVerificationStatus: string
{
    case NonRequis = 'non_requis';
    case EnAttente = 'en_attente';
    case Conforme = 'conforme';
    case NonConforme = 'non_conforme';

    public function label(): string
    {
        return trans("document.verification_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::NonRequis, self::Conforme => 'success',
            self::EnAttente => 'warning',
            self::NonConforme => 'danger',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
