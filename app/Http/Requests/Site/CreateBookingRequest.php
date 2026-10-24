<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de création d'une réservation : le « Réserver » de la fiche
 * véhicule, qui crée le panier avec le prix figé avant tout formulaire.
 */
class CreateBookingRequest extends FormRequest
{
    /**
     * Aucune authentification requise : le public réserve en mode invité.
     * Les règles de BookingService (double réservation, âge, planning des
     * chauffeurs) restent dans le service.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'du' => ['required', 'date_format:Y-m-d'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer'],
            'heure_retour' => ['required', 'integer'],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ];
    }
}
