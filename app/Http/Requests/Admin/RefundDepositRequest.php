<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentKind;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Restitution de la caution. Les prélèvements éventuels (carburant, dégâts,
 * retard) sont saisis à part : le client doit voir la ligne avant de
 * récupérer ce qui reste.
 */
class RefundDepositRequest extends FormRequest
{
    /**
     * Les rôles sont vérifiés par les middlewares de route : toute requête
     * validée ici vient déjà d'un accès autorisé.
     */
    public function authorize(): bool
    {
        abort_unless($this->route('payment')->kind === PaymentKind::Deposit, 422);

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'deducted_amount' => ['nullable', 'integer', 'min:0', 'max:'.$this->route('payment')->amount],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
