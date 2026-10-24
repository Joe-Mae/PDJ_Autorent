<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Signature du contrat de location sur la tablette.
 */
class SignPickupContractRequest extends FormRequest
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
            'signature' => ['required', 'string', 'max:255'],
            'accepted_conditions' => ['required', 'accepted'],
        ];
    }
}
