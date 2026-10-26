<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de recherche d'une réservation : par référence ou par nom du
 * réservateur. L'un des deux champs suffit.
 */
class LookupBookingRequest extends FormRequest
{
    /** Aucune authentification requise : le jeton du lien fait foi. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'min:3', 'max:20', 'required_without:customer_name'],
            'customer_name' => ['nullable', 'string', 'min:3', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reference.required_without' => __('booking.lookup.identifier_required'),
            'reference.min' => __('booking.lookup.reference_min'),
            'customer_name.min' => __('booking.lookup.name_min'),
        ];
    }
}
