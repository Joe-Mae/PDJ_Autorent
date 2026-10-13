<?php

namespace App\Enums;

/**
 * Aucune encaissement en ligne : ces montants sont saisis à l'agence.
 * On trace tout de même le moyen de paiement pour la caisse du jour.
 */
enum PaymentKind: string
{
    case Rental = 'location';
    case Deposit = 'caution';
    case DepositRefund = 'caution_restitution';
    case LateFee = 'penalite_retard';
    case Fuel = 'carburant';
    case Damage = 'degat';

    public function isCashIn(): bool
    {
        return in_array($this, [self::Rental, self::Deposit, self::LateFee, self::Fuel, self::Damage], true);
    }

    public function isCashOut(): bool
    {
        return $this === self::DepositRefund;
    }

    public function label(): string
    {
        return trans("payment.kind.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
