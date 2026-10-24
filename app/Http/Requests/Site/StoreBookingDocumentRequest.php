<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Concerns\GuardsBookingDocuments;
use App\Http\Requests\Concerns\ValidatesDocumentDeposit;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dépôt d'une pièce depuis le tunnel de réservation authentifié.
 */
class StoreBookingDocumentRequest extends FormRequest
{
    use GuardsBookingDocuments;
    use ValidatesDocumentDeposit;

    /** Garde de propriété et résolution finale : contrôlées avant la validation. */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        abort_unless($booking instanceof Booking, 404);

        $this->guardDocumentOwnership($this, $booking);
        abort_if($booking->status->isFinal(), 422);

        return true;
    }
}
