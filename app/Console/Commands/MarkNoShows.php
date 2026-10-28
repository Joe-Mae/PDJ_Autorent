<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;

/**
 * Annule les réservations dont le client ne s'est pas présenté au retrait.
 *
 * Deux temps : un rappel est affiché aux agents 2 h après l'heure convenue
 * (pop-up d'absence), puis la réservation est annulée automatiquement à
 * l'échéance de grâce — 5 h par défaut. Le véhicule redevient disponible et
 * l'absence est comptée au client.
 *
 * L'échéance est portée par la colonne grace_expires_at, posée à la création,
 * recalculée à chaque modification de dates et repoussée quand un agent décale
 * l'heure de retrait : pas de calcul à refaire ici.
 */
class MarkNoShows extends Command
{
    protected $signature = 'bookings:mark-no-shows';

    protected $description = 'Annule les réservations confirmées non honorées après le délai de grâce';

    public function handle(BookingService $bookings): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::Confirmee->value)
            ->whereNotNull('grace_expires_at')
            ->where('grace_expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Aucune réservation en attente de grâce.');

            return self::SUCCESS;
        }

        foreach ($expired as $booking) {
            $bookings->markAsNoShow($booking);
            $this->line("  {$booking->reference} → Non présenté");
        }

        $this->info("{$expired->count()} réservation(s) passée(s) en « Non présenté ».");

        return self::SUCCESS;
    }
}
