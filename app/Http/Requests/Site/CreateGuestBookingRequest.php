<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de réservation invitée : écran récapitulatif du tunnel invité,
 * sur la fiche du véhicule.
 */
class CreateGuestBookingRequest extends FormRequest
{
    /**
     * Aucune authentification requise : le public réserve en mode invité.
     * Le garde-fou — le véhicule doit être réservable — est porté ici.
     */
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        abort_unless($vehicle->isBookable(), 404);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'du' => ['nullable', 'date_format:Y-m-d', 'required_with:au', 'after_or_equal:today'],
            'au' => ['nullable', 'date_format:Y-m-d', 'required_with:du', 'after_or_equal:du'],
            'heure_depart' => ['nullable', 'integer'],
            'heure_retour' => ['nullable', 'integer'],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ];
    }
}
