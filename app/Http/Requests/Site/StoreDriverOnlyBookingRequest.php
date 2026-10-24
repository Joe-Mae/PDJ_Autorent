<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de demande « chauffeur seul », sans véhicule.
 */
class StoreDriverOnlyBookingRequest extends FormRequest
{
    /** Aucune authentification requise : le public commande un chauffeur. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,30}$/'],
            'du' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'au' => ['required', 'date_format:Y-m-d', 'after_or_equal:du'],
            'identity_document' => [
                'required', 'file', 'max:5120',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
            ],
        ];
    }
}
