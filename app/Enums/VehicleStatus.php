<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Disponible = 'disponible';
    case Reserve = 'reserve';
    case EnLocation = 'en_location';
    case EnMaintenance = 'en_maintenance';
    case HorsService = 'hors_service';

    /** Un véhicule réservable par le public. */
    public function isBookable(): bool
    {
        return in_array($this, [self::Disponible, self::Reserve, self::EnLocation], true);
    }

    /** @return list<string> */
    public static function bookableValues(): array
    {
        return array_values(array_map(
            static fn (self $case) => $case->value,
            array_filter(self::cases(), static fn (self $case) => $case->isBookable()),
        ));
    }

    /** Un seul véhicule ne peut pas avoir deux statuts concurrent : celui-ci gagne. */
    public function beats(self $other): bool
    {
        return match ($this) {
            self::HorsService => true,
            self::EnMaintenance => $other !== self::HorsService,
            self::EnLocation => ! in_array($other, [self::HorsService, self::EnMaintenance], true),
            self::Reserve => $other === self::Disponible,
            self::Disponible => false,
        };
    }

    public function label(): string
    {
        return trans("vehicle.status.{$this->value}");
    }

    /** @return list<string> Valeurs acceptees par la colonne enum en base. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Classe Bootstrap du badge affiche dans l'admin. */
    public function color(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::Reserve => 'info',
            self::EnLocation => 'primary',
            self::EnMaintenance => 'warning',
            self::HorsService => 'secondary',
        };
    }
}
