<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Disponible = 'disponible';
    case Reserve = 'reserve';
    case EnLocation = 'en_location';
    case EnMaintenance = 'en_maintenance';
    case HorsService = 'hors_service';

    /**
     * Un véhicule réservable par le public.
     *
     * `EnLocation` est volontairement exclu : la voiture est chez le client,
     * elle ne doit plus figurer dans la flotte disponible tant que l'agent
     * n'a pas enregistré son retour. Le statut repasse à `Disponible` à ce
     * moment-là, et la voiture réapparaît d'elle-même.
     */
    public function isBookable(): bool
    {
        return in_array($this, [self::Disponible, self::Reserve], true);
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

    /** Teinte du badge, définie dans `app.scss` (bloc « Couleurs de statut »). */
    public function color(): string
    {
        return match ($this) {
            self::Disponible => 'valide',
            self::Reserve => 'attente',
            self::EnLocation => 'encours',
            self::EnMaintenance => 'maintenance',
            self::HorsService => 'refusee',
        };
    }
}
