<?php

namespace App\Http\Requests\Agent;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Contrôle des originaux avant le départ du véhicule.
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
            'driver_license_original_verified' => ['nullable', 'boolean'],
            'driver_license_category_authorized' => ['nullable', 'boolean'],
            'driver_license_category' => ['nullable', 'string', 'max:30'],
            'driver_license_issued_at' => ['nullable', 'date_format:Y-m-d'],
            'driver_license_expires_at' => ['nullable', 'date_format:Y-m-d'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
            'license_copy_consent' => ['sometimes', 'accepted'],
            'license_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
