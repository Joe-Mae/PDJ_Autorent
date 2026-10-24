<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Concerns\GuardsBookingDocuments;
use App\Http\Requests\Concerns\ValidatesDocumentDeposit;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dépôt d'une pièce pour une demande sans compte : le jeton du lien fait
 * office de preuve de possession.
 */
class StoreGuestDocumentRequest extends FormRequest
{
    use GuardsBookingDocuments;
    use ValidatesDocumentDeposit;

    /** Jeton du lien et dépôt encore ouvert : contrôlés avant la validation. */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        abort_unless($booking instanceof Booking, 404);

        $this->guardGuestToken($booking, (string) $this->route('token'));
        abort_unless($booking->status->canStillUploadDocuments(), 422);

        return true;
    }
}
