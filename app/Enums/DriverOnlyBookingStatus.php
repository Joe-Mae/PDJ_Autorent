<?php

namespace App\Enums;

/**
 * Cycle de vie d'une réservation de chauffeur seul.
 *
 *   en_attente ──▶ confirmee ──▶ en_cours ──▶ terminee
 *        │             │            │
 *        ▼             ▼            ▼
 *     refusee       annulee      annulee
 */
enum DriverOnlyBookingStatus: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Refusee = 'refusee';
    case Annulee = 'annulee';

    /** La prestation immobilise encore le chauffeur dans le planning. */
    public function isActive(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee, self::EnCours], true);
    }

    public function canBeRefused(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee], true);
    }

    public function canBeCancelled(): bool
    {
        return $this->isActive();
    }

    public function canStart(): bool
    {
        return $this === self::Confirmee;
    }

    public function canFinish(): bool
    {
        return $this === self::EnCours;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Terminee, self::Refusee, self::Annulee], true);
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
            self::EnCours => 'encours',
            self::Terminee => 'terminee',
            self::Refusee => 'refusee',
            self::Annulee => 'annulee',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
