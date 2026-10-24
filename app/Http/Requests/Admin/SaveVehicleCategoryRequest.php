<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création ou mise à jour d'une catégorie de véhicule depuis l'administration.
 */
class SaveVehicleCategoryRequest extends FormRequest
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
            'name_fr' => ['required', 'string', 'max:80'],
            'name_en' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', Rule::unique('vehicle_categories', 'slug')->ignore($this->route('category')?->id)],
            'description_fr' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'daily_price' => ['required', 'integer', 'min:1000'],
            'deposit_amount' => ['required', 'integer', 'min:0'],
            'daily_price_holiday' => ['nullable', 'integer', 'min:1000'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'doors' => ['required', 'integer', 'between:2,6'],
            'bags' => ['required', 'integer', 'between:0,9'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}
