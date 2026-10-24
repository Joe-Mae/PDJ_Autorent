<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de connexion du personnel.
 */
class LoginRequest extends FormRequest
{
    /**
     * Aucune authentification requise. Le contrôle du compte (identifiant
     * trouvé, rôle du personnel, mot de passe) reste dans le contrôleur :
     * il doit répondre comme un mot de passe faux, sans révéler si une
     * fiche existe.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
