<?php

namespace App\Http\Requests\Agent;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Contrôle de l'identité avant le départ du véhicule.
 *
 * Le permis n'est pas contrôlé ici : il l'est au moment de la remise des clés,
 * par la caissière, qui le photographie (voir HandoverBookingRequest).
 */
class StorePickupDocumentsRequest extends FormRequest
{
    /**
     * La réservation doit être confirmée : même garde-fou que l'écran,
     * déplacé ici pour s'appliquer avant la validation.
     */
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        abort_unless($booking->status === BookingStatus::Confirmee, 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'identity_original_verified' => ['nullable', 'boolean'],
            'identity_name_matches' => ['nullable', 'boolean'],
            'customer_age_eligible' => ['nullable', 'boolean'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
