<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;

/**
 * Passe en « Non présenté » les réservations dont le délai de grâce est écoulé.
 *
 * Le délai de grâce est porté par la colonne grace_expires_at, posée à la
 * création puis recalculée à chaque modification de dates : pas de calcul à
 * refaire ici, la commande se contente de lire la colonne.
 */
class MarkNoShows extends Command
{
    protected $signature = 'bookings:mark-no-shows';

    protected $description = 'Passe en « Non présenté » les réservations confirmées non honorées';

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
