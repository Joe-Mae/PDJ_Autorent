<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Règle métier violée, avec un message déjà prêt à afficher au client.
 *
 * On la distingue d'une erreur de validation (400) parce que la réaction attendue
 * n'est pas « corrige ton formulaire » mais « propose autre chose » : la page de
 * results propose les alternatives, le tunnel propose de changer de dates.
 */
class BookingException extends RuntimeException
{
    public static function vehicleTaken(): self
    {
        return new self(__('booking.error.vehicle_just_taken'));
    }

    public static function noDriver(): self
    {
        return new self(__('booking.error.no_driver_available'));
    }

    public static function tooManyBookings(int $max): self
    {
        return new self(__('booking.error.too_many_bookings', ['max' => $max]));
    }
}
