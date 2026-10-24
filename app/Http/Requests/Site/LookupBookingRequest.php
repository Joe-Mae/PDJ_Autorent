<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de recherche d'une demande invitée par sa référence.
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
            'reference' => ['required', 'string', 'max:20'],
        ];
    }
}
