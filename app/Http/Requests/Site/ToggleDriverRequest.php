<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Concerns\GuardsBookingOwnership;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bascule de l'option chauffeur sur une réservation du tunnel.
 */
class ToggleDriverRequest extends FormRequest
{
    use GuardsBookingOwnership;

    /**
     * La garde de propriété et le statut final sont contrôlés avant la
     * validation, comme le faisait le contrôleur : l'ordre ne bouge pas.
     */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        abort_unless($booking instanceof Booking, 404);

        $this->guardOwnership($this, $booking);
        abort_if($booking->status->isFinal(), 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return ['avec_chauffeur' => ['required', 'boolean']];
    }
}
