<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

/**
 * Rappel J-1 sur WhatsApp, avec un bouton « Je confirme ma venue ».
 *
 * Ce message est le meilleur investissement anti-fantôme du site : un client qui
 * répond d'un clic n'a aucune raison de ne pas venir.
 */
class SendPickupReminders extends Command
{
    protected $signature = 'bookings:send-reminders {--date= : Date des retraits visés (Y-m-d), défaut demain}';

    protected $description = 'Envoie le rappel de venue J-1 aux réservations confirmées';

    public function handle(BookingService $bookings, NotificationService $notifications): int
    {
        $date = $this->option('date')
            ? Date::parse($this->option('date'))
            : Date::today()->addDay();

        $pending = Booking::query()
            ->where('status', BookingStatus::Confirmee->value)
            ->whereDate('starts_at', $date->toDateString())
            ->whereNull('reminder_sent_at')
            ->whereNull('client_confirmed_presence_at')
            ->with(['vehicle', 'category', 'user'])
            ->get();

        if ($pending->isEmpty()) {
            $this->info("Aucun retrait le {$date->toDateString()}.");

            return self::SUCCESS;
        }

        foreach ($pending as $booking) {
            $sent = $notifications->sendPickupReminder($booking);

            if ($sent) {
                $bookings->markReminderSent($booking);
                $this->line("  {$booking->reference} → rappel envoyé");
            } else {
                $this->warn("  {$booking->reference} → envoi impossible");
            }
        }

        $this->info("{$pending->count()} rappel(s) traité(s).");

        return self::SUCCESS;
    }
}
