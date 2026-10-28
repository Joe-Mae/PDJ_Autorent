<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Annulation d'une réservation depuis le pop-up d'absence.
 *
 * Le motif est obligatoire : c'est la seule trace qui explique au client, et
 * plus tard à l'équipe, pourquoi la voiture n'est pas partie.
 */
class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'motif.required' => __('admin.reason_required'),
        ];
    }
}
