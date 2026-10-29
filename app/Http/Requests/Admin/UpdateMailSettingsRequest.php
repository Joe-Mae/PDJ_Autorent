<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Réglages SMTP saisis par le super-administrateur.
 *
 * L'adresse d'expédition est obligatoire : sans elle, aucun serveur ne prend
 * le message. Le mot de passe, lui, est facultatif au quotidien — le laisser
 * vide conserve celui déjà enregistré.
 */
class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'clear_password' => ['nullable', 'boolean'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'notification_address' => ['nullable', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from_address.required' => __('admin.mail_from_required'),
            'from_address.email' => __('admin.mail_email_invalid'),
            'notification_address.email' => __('admin.mail_email_invalid'),
            'encryption.required' => __('admin.mail_encryption_required'),
        ];
    }
}
