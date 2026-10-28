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
     * Un véhicule que le public peut réserver maintenant.
     *
     * `EnLocation` est exclu : la voiture est chez le client, on ne peut pas
     * la promettre à quelqu'un d'autre tant qu'elle n'est pas revenue.
     */
    public function isBookable(): bool
    {
        return in_array($this, [self::Disponible, self::Reserve], true);
    }

    /**
     * Un véhicule qui figure dans la flotte publique, même occupé.
     *
     * Une voiture partie reste visible avec un badge « indisponible » : le
     * client sait qu'elle existe et pourra revenir quand elle sera libre. Seul
     * ce qui est immobilisé (maintenance, hors service) sort du catalogue.
     */
    public function isListed(): bool
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

    /** @return list<string> */
    public static function listedValues(): array
    {
        return array_values(array_map(
            static fn (self $case) => $case->value,
            array_filter(self::cases(), static fn (self $case) => $case->isListed()),
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
