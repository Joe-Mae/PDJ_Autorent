<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Especes = 'especes';
    case MobileMoney = 'mobile_money';
    case Visa = 'visa';

    /** Moyens acceptés pour la caution, selon la trésorerie réelle de l'agence. */
    public static function depositMethods(): array
    {
        return [self::Especes, self::MobileMoney];
    }

    public function label(): string
    {
        return trans("payment.method.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
