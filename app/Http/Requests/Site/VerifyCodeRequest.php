<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de saisie du code de vérification du téléphone.
 */
class VerifyCodeRequest extends FormRequest
{
    /**
     * Aucune authentification requise : le client est au milieu du tunnel,
     * sans compte.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }
}
