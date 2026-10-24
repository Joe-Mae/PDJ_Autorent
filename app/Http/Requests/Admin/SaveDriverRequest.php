<?php

namespace App\Http\Requests\Admin;

use App\Enums\DriverStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création ou mise à jour d'un chauffeur depuis l'administration.
 */
class SaveDriverRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:20', Rule::unique('drivers', 'phone')->ignore($this->route('driver')?->id)],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(array_column(DriverStatus::cases(), 'value'))],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }
}
