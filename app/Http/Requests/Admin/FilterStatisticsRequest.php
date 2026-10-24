<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtre de période du tableau de bord de statistiques.
 */
class FilterStatisticsRequest extends FormRequest
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
            'periode' => ['nullable', 'in:semaine,mois,annee'],
        ];
    }
}
