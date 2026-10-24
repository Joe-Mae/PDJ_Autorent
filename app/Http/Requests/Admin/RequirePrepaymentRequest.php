<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Exigence d'une caution anticipée pour un client depuis l'administration.
 */
class RequirePrepaymentRequest extends FormRequest
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
            'required' => ['required', 'boolean'],
        ];
    }
}
