<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de recherche d'une réservation, par référence ou par nom du client.
 */
class LookupBookingRequest extends FormRequest
{
    /** Aucune authentification requise : le jeton du lien fait foi. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }
}
