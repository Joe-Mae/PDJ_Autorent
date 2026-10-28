<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Décalage de l'heure de retrait après un appel du client.
 *
 * L'agent peut soit choisir une heure précise, soit utiliser un raccourci
 * (« dans 2 h »). Les deux formes sont acceptées, jamais les deux à la fois :
 * c'est l'une ou l'autre qui fixe la nouvelle échéance.
 */
class ExtendPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'pickup_at' => ['nullable', 'date', 'after:now'],
            'heures' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pickup_at.after' => __('admin.extend_in_past'),
        ];
    }

    /** Il faut au moins une des deux formes, sinon rien ne change. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('pickup_at') && ! $this->filled('heures')) {
                $validator->errors()->add('heures', __('admin.extend_required'));
            }
        });
    }
}
