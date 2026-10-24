<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Restitution de la caution, avec les prélèvements détaillés.
 */
class RefundDepositRequest extends FormRequest
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
            'deducted_amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
