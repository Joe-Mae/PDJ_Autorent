<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sélection de véhicules pour une action en masse : suppression, archivage
 * ou remise en service. Les véhicules archivés restent en base, d'où la même
 * validation pour toutes ces actions.
 */
class VehicleSelectionRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:vehicles,id'],
        ];
    }
}
