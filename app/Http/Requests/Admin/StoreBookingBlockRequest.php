<?php

namespace App\Http\Requests\Admin;

use App\Enums\BlockReason;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création d'un blocage d'atelier depuis le planning.
 */
class StoreBookingBlockRequest extends FormRequest
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
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['required', 'in:'.implode(',', array_column(BlockReason::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
