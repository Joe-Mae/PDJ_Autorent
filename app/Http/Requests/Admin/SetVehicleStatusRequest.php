<?php

namespace App\Http\Requests\Admin;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Passage rapide de statut d'un véhicule depuis la liste
 * (disponible / maintenance / hors service).
 */
class SetVehicleStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_column(VehicleStatus::cases(), 'value'))],
        ];
    }
}
