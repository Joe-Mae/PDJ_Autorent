<?php

namespace App\Http\Requests\Concerns;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Garde de propriété d'une réservation du tunnel.
 *
 * Elle est partagée entre le contrôleur et les Form Requests du même tunnel :
 * les Form Request exécutent leurs contrôles dans authorize(), AVANT la
 * validation, exactement comme le faisait le contrôleur — l'ordre « garde,
 * puis validation » doit rester identique, sinon une erreur de formulaire
 * révélerait l'existence d'une réservation à qui n'a pas le droit de la voir.
 *
 * Le rattachement d'une réservation orpheline au compte qui l'a créée est un
 * effet de bord assumé de cette garde : il a lieu avant la validation, comme
 * par le passé.
 */
trait GuardsBookingOwnership
{
    private function guardOwnership(Request $request, Booking $booking): void
    {
        abort_if($booking->guest_access_token_hash !== null, 404);

        $user = $request->user();

        if ($user === null) {
            return;
        }

        if ($booking->user_id === null) {
            // Une réservation sans compte appartient à celui qui l'a créée dans
            // ce navigateur. La session en garde la trace : rattacher n'importe
            // quelle réservation orpheline à n'importe quel compte connecté
            // suffirait à prendre le contrôle d'une réservation dont on
            // connaît la référence, lue au téléphone : documents et
            // confirmation compris.
            $owned = (array) $request->session()->get('pdj.reservations', []);

            abort_unless(in_array($booking->reference, $owned, true), 403);

            $booking->update(['user_id' => $user->id]);

            return;
        }

        abort_unless($booking->user_id === $user->id || $user->isStaff(), 403);
    }
}
