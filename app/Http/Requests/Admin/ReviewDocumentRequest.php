<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Verdict sur un document client depuis la file de vérification.
 */
class ReviewDocumentRequest extends FormRequest
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
            'verdict' => ['required', 'in:conforme,non_conforme,a_relever'],
            'note' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'verdict.required' => __('admin.verdict_required'),
        ];
    }
}
