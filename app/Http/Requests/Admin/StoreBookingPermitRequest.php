<?php

namespace App\Http\Requests\Admin;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dépôt du permis du client depuis le dossier de réservation.
 *
 * Le permis est contrôlé en agence, pas à distance : n'importe quel employé
 * peut joindre la photo au dossier, mais la case de consentement est
 * obligatoire. Une prestation avec chauffeur n'a pas de permis à joindre.
 */
class StoreBookingPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        abort_if($booking->with_driver, 422);
        abort_if($booking->status->isFinal(), 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'permit_consent' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'permit.required' => __('admin.permit_required'),
            'permit_consent.required' => __('admin.permit_consent_required'),
            'permit_consent.accepted' => __('admin.permit_consent_required'),
        ];
    }
}
