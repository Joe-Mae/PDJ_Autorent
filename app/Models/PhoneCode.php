<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Preuve d'un numéro de téléphone. Seul le hash du code est conservé : une fuite
 * de la table ne rendrait aucun code exploitable.
 */
#[Fillable(['phone', 'code_hash', 'channel', 'attempts', 'expires_at', 'verified_at', 'ip'])]
class PhoneCode extends Model
{
    public const MAX_ATTEMPTS = 3;

    public const LIFETIME_MINUTES = 10;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function attemptsLeft(): int
    {
        return max(0, self::MAX_ATTEMPTS - $this->attempts);
    }

    public function isUsable(): bool
    {
        return ! $this->isVerified() && ! $this->isExpired() && $this->attemptsLeft() > 0;
    }
}
