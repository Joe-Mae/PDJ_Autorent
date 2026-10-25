<?php

namespace App\Http\Requests\Agent;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Remise des clés : contrôle du permis, puis départ du véhicule.
 *
 * Le permis du locataire est vérifié à l'agence, au dernier moment. La photo
 * prise par la caissière est la preuve du contrôle : sans photo au dossier, la
 * location ne démarre pas. Si un autre employé a déjà joint le permis depuis le
 * dossier de la réservation, une nouvelle photo n'est pas exigée. Une prestation
 * avec chauffeur n'exige pas de permis, c'est le chauffeur qui conduit.
 */
class HandoverBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        abort_unless($booking->status === BookingStatus::Confirmee, 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        $booking = $this->route('booking');
        $needsLicense = ! $booking->with_driver;
        $licenseOnFile = $needsLicense && $booking->hasDriverLicenseCopy();

        return [
            'driver_license_original_verified' => ['nullable', 'boolean'],
            'driver_license_category_authorized' => ['nullable', 'boolean'],
            'driver_license_category' => ['nullable', 'string', 'max:30'],
            'driver_license_issued_at' => ['nullable', 'date_format:Y-m-d'],
            'driver_license_expires_at' => ['nullable', 'date_format:Y-m-d'],
            'license_copy' => match (true) {
                ! $needsLicense => ['prohibited'],
                $licenseOnFile => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                default => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            },
            'license_copy_consent' => match (true) {
                ! $needsLicense => ['prohibited'],
                // `accepted` est une règle implicite : `nullable` ne la saute
                // pas quand la case est absente du formulaire, d'où `sometimes`.
                $licenseOnFile => ['sometimes', 'accepted'],
                default => ['required', 'accepted'],
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'license_copy.required' => __('agent.license_photo_required'),
            'license_copy_consent.required' => __('agent.license_copy_consent_required'),
            'license_copy_consent.accepted' => __('agent.license_copy_consent_required'),
        ];
    }
}
