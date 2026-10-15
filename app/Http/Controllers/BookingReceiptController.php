<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Bon de réservation, en style ticket.
 *
 * Ce PDF sert trois usages : c'est le récapitulatif que le client emporte, la
 * fiche que l'agent ouvre au guichet, et le justificatif que le client
 * présente à son employeur. Il doit donc tenir sur une page et se lire à deux.
 */
class BookingReceiptController extends Controller
{
    public function __invoke(Request $request, string $locale, Booking $booking): Response
    {
        $user = $request->user();

        if ($booking->guest_access_token_hash !== null) {
            abort_unless($user?->isStaff() ?? false, 404);
        }

        $period = BookingPeriod::fromDates(
            $booking->starts_at->toImmutable(),
            $booking->ends_at->toImmutable(),
        );

        $pdf = Pdf::loadView('booking.receipt', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
            'period' => $period,
            'settings' => app(SettingsService::class),
        ])->setPaper('a5');

        return $pdf->download("PDJ-{$booking->reference}.pdf");
    }
}
