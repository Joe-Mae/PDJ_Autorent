<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverOnlyBookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DriverOnlyBooking;
use Illuminate\Http\JsonResponse;

/**
 * Fil des nouvelles demandes pour la cloche de l'administration.
 *
 * Le back-office n'a pas de websocket : la page interroge ce point toutes les
 * quinze secondes. On renvoie la dernière demande en attente (location ou
 * chauffeur seul) et le nombre total, pour que la cloche s'allume et qu'un
 * pop-up sonore prévienne l'équipe sans recharger l'écran.
 *
 * Aucune donnée sensible : ce que la liste des réservations montre déjà aux
 * employés.
 */
class NotificationFeedController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $booking = Booking::query()
            ->where('status', BookingStatus::EnAttente->value)
            ->with(['vehicle', 'user'])
            ->latest('created_at')
            ->first();

        $driverOnly = DriverOnlyBooking::query()
            ->where('status', DriverOnlyBookingStatus::EnAttente->value)
            ->latest('created_at')
            ->first();

        $latest = collect([
            $booking !== null ? $this->bookingPayload($booking) : null,
            $driverOnly !== null ? $this->driverOnlyPayload($driverOnly) : null,
        ])
            ->filter()
            ->sortByDesc('created_at')
            ->first();

        return response()->json([
            'pending' => Booking::query()->where('status', BookingStatus::EnAttente->value)->count()
                + DriverOnlyBooking::query()->where('status', DriverOnlyBookingStatus::EnAttente->value)->count(),
            'latest' => $latest,
        ]);
    }

    /** @return array<string, string> */
    private function bookingPayload(Booking $booking): array
    {
        return [
            'kind' => 'booking',
            'id' => (string) $booking->getKey(),
            'created_at' => $booking->created_at->toIso8601String(),
            'reference' => $booking->reference,
            'customer' => $booking->user?->displayName() ?? $booking->customer_name ?? '—',
            'vehicle' => $booking->vehicle?->displayName() ?? '—',
            'url' => route('admin.bookings.show', ['booking' => $booking]),
        ];
    }

    /** @return array<string, string> */
    private function driverOnlyPayload(DriverOnlyBooking $booking): array
    {
        return [
            'kind' => 'driver_only',
            'id' => (string) $booking->getKey(),
            'created_at' => $booking->created_at->toIso8601String(),
            'reference' => $booking->reference,
            'customer' => $booking->customer_name,
            'vehicle' => __('driver.solo_title'),
            'url' => route('admin.driver-only-bookings.index'),
        ];
    }
}
