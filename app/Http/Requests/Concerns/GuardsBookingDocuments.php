<?php

namespace App\Http\Requests\Concerns;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Accès au dépôt de pièces d'une réservation.
 *
 * Partagé entre BookingDocumentController et ses deux Form Requests : les
 * gardes doivent passer dans authorize(), avant la validation, pour garder
 * l'ordre exact du contrôleur — une erreur de formulaire ne doit jamais
 * devancer le 404 d'un jeton invalide.
 */
trait GuardsBookingDocuments
{
    /** Dépôt rattaché à un compte : le jeton invité rend la réservation invisible. */
    private function guardDocumentOwnership(Request $request, Booking $booking): void
    {
        abort_if($booking->guest_access_token_hash !== null, 404);

        $user = $request->user();

        if ($user === null) {
            return;
        }

        abort_unless($booking->user_id === $user->id || $user->isStaff(), 403);
    }

    /** Réservation invitée : le hash stocké et le jeton du lien doivent coïncider. */
    private function guardGuestToken(Booking $booking, string $token): void
    {
        abort_unless(
            $booking->user_id === null
                && $booking->guest_access_token_hash !== null
                && hash_equals($booking->guest_access_token_hash, hash('sha256', $token)),
            404,
        );
    }
}
