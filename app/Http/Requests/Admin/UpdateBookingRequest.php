<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Re-dater une réservation depuis l'administration : le prix est recalculé
 * et réécrit.
 */
class UpdateBookingRequest extends FormRequest
{
    /**
     * Les rôles sont vérifiés par les middlewares de route : toute requête
     * validée ici vient déjà d'un accès autorisé.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'du' => ['required', 'date'],
            'au' => ['required', 'date', 'after_or_equal:du'],
            'heure_depart' => ['required', 'integer', 'between:6,22'],
            'heure_retour' => ['required', 'integer', 'between:6,22'],
            'avec_chauffeur' => ['nullable', 'boolean'],
        ];
    }
}
