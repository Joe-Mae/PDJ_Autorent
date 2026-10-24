<?php

namespace App\Http\Requests\Agent;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Prise de la caution au départ, sur un reçu distinct.
 */
class StorePickupDepositRequest extends FormRequest
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
            'method' => ['required', Rule::in(array_column(PaymentMethod::depositMethods(), 'value'))],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
