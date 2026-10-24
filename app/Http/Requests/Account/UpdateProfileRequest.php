<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour du profil du client.
 */
class UpdateProfileRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s]{8,20}$/', Rule::unique('users', 'phone')->ignore($this->user()->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'birth_date' => ['required', 'date', 'before:today'],
            'preferred_locale' => ['required', 'in:fr,en'],
        ];
    }
}
