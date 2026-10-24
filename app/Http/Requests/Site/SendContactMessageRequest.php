<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulaire de contact du site.
 */
class SendContactMessageRequest extends FormRequest
{
    /** Aucune authentification requise : le visiteur écrit à l'agence. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:1500'],
        ];
    }
}
