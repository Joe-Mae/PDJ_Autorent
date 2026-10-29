<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Envoi d'un e-mail de test depuis la page de configuration.
 *
 * L'adresse est facultative : sans elle, le test part vers la boîte de
 * réception configurée, ce qui vérifie le chemin réel des demandes.
 */
class SendTestMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
