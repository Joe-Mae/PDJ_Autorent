<?php

namespace App\Enums;

/**
 * Cycle de vie d'une réservation.
 *
 *   en_attente ──▶ confirmee ──▶ en_cours ──▶ terminee
 *        │             │             │
 *        ▼             ▼             ▼
 *    annulee       refusee      (retard)
 *                      │
 *                      ▼
 *                non_presente
 */
enum BookingStatus: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Annulee = 'annulee';
    case Refusee = 'refusee';
    case NonPresente = 'non_presente';

    /** Une réservation qui immobilise encore le véhicule dans le planning. */
    public function isBlocking(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee, self::EnCours], true);
    }

    /** Réservation en cours ou à venir : compte pour le maximum de 2 actives. */
    public function isActive(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee, self::EnCours], true);
    }

    /** Le véhicule est parti : plus rien ne peut être modifié. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Terminee, self::Annulee, self::Refusee, self::NonPresente], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee], true);
    }

    public function canStillUploadDocuments(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee], true);
    }

    public function label(): string
    {
        return trans("booking.status.{$this->value}");
    }

    /**
     * Teinte du badge, définie dans `app.scss` (bloc « Couleurs de statut »).
     *
     * Le vert de la marque ne peut pas distinguer « confirmé » de « terminé » :
     * chaque étape a donc sa propre couleur, et les issues défavorables
     * (refus, absence) restent rouges ou anthracite.
     */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'attente',
            self::Confirmee => 'confirmee',
            self::EnCours => 'encours',
            self::Terminee => 'terminee',
            self::Annulee => 'annulee',
            self::Refusee => 'refusee',
            self::NonPresente => 'absence',
        };
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
