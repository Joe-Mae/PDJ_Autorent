<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * État des lieux au départ : inspection visuelle, kilométrage et photos.
 *
 * L'agent fait le tour de la voiture. Tant qu'il n'y a rien, aucune photo
 * n'est demandée. Dès qu'une rayure ou un choc est constaté, la description
 * et les quatre photos deviennent obligatoires : c'est la preuve qui protège
 * l'agence comme le client.
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
        $damage = $this->boolean('damage_present');

        return [
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['required', Rule::in(['quarter', 'half', 'three_quarters', 'full'])],
            'damage_present' => ['required', 'boolean'],
            'damage_reported' => $damage
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'],
            'photos' => $damage
                ? ['required', 'array', 'min:4', 'max:10']
                : ['nullable', 'array', 'max:10'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'damage_present.required' => __('agent.inspection_required'),
            'damage_reported.required' => __('agent.damage_required'),
            'photos.min' => __('agent.at_least_photos', ['count' => 4]),
            'photos.required' => __('agent.at_least_photos', ['count' => 4]),
        ];
    }
}
