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

        // La référence porte déjà le préfixe de l'agence : c'est elle qui
        // nomme le fichier. Écrire la marque ici ajouterait un nom codé en dur
        // — et un second préfixe, puisque « PDJ-8F3K2A » devenait
        // « PDJ-PDJ-8F3K2A.pdf ». Un changement de marque suit donc tout seul.
        return $pdf->download($booking->reference.'.pdf');
    }
}
