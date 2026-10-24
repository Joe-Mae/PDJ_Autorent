<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * État des lieux au départ : photos et kilométrage.
 */
class StorePickupConditionReportRequest extends FormRequest
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
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['required', Rule::in(['quarter', 'half', 'three_quarters', 'full'])],
            'damage_reported' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required', 'array', 'min:4', 'max:10'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photos.min' => __('agent.at_least_photos', ['count' => 4]),
        ];
    }
}
