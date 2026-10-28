<?php

namespace App\Http\Requests\Site;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtres de recherche de la flotte (query string).
 *
 * Toutes les clés sont facultatives : le visiteur arrive sur /vehicules sans
 * rien choisir, et le lien du menu doit toujours afficher la flotte. Sans
 * dates, c'est le contrôleur qui retombe sur le créneau par défaut.
 */
class SearchVehiclesRequest extends FormRequest
{
    /**
     * La fiche reste consultable tant que la voiture est au catalogue, même
     * occupée : le client doit pouvoir la voir et comprendre qu'elle est prise
     * pour l'instant. Seuls les véhicules retirés (maintenance, hors service,
     * inactifs) renvoient 404.
     */
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        if ($vehicle instanceof Vehicle) {
            abort_unless($vehicle->is_active && $vehicle->status->isListed(), 404);
        }

        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'du' => ['nullable', 'date_format:Y-m-d'],
            'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du'],
            'heure_depart' => ['nullable', 'integer', 'between:6,22'],
            'heure_retour' => ['nullable', 'integer', 'between:6,22'],
            'avec_chauffeur' => ['nullable', 'boolean'],
            'categorie' => ['nullable', 'string', 'exists:vehicle_categories,slug'],
            'transmission' => ['nullable', 'in:manuelle,automatique'],
            'fuel' => ['nullable', 'array'],
            'fuel.*' => ['in:essence,diesel,hybride,electrique'],
            'places_min' => ['nullable', 'integer', 'between:2,9'],
            'prix_min' => ['nullable', 'integer', 'min:0'],
            'prix_max' => ['nullable', 'integer', 'min:0'],
            'tri' => ['nullable', 'in:prix-asc,prix-desc'],
        ];
    }
}
