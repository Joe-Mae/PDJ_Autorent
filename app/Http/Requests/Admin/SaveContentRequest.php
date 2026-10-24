<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Enregistrement d'un contenu éditable (bannière, FAQ, avis, jour férié).
 *
 * Le type de contenu vient de la route : chaque famille a ses champs, donc
 * ses règles. Un type inconnu ne produit aucune règle — la validation passe,
 * et c'est le contrôleur qui renvoie son 404 via config(), comme par le passé.
 */
class SaveContentRequest extends FormRequest
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
        return match ($this->route('type')) {
            'bannieres' => [
                'title_fr' => ['required', 'string', 'max:150'],
                'title_en' => ['nullable', 'string', 'max:150'],
                'subtitle_fr' => ['nullable', 'string', 'max:500'],
                'subtitle_en' => ['nullable', 'string', 'max:500'],
                'slot' => ['required', Rule::in(['home_hero', 'home_middle', 'home_bottom'])],
                'is_active' => ['nullable', 'boolean'],
            ],
            'faq' => [
                'category_fr' => ['required', 'string', 'max:80'],
                'category_en' => ['required', 'string', 'max:80'],
                'question_fr' => ['required', 'string', 'max:255'],
                'question_en' => ['required', 'string', 'max:255'],
                'answer_fr' => ['required', 'string', 'max:3000'],
                'answer_en' => ['nullable', 'string', 'max:3000'],
                'sort_order' => ['required', 'integer', 'min:0'],
                'is_published' => ['nullable', 'boolean'],
            ],
            'avis' => [
                'id' => ['nullable', 'exists:reviews,id'],
                'status' => ['nullable', Rule::in(array_column(ReviewStatus::cases(), 'value'))],
                'agency_answer' => ['nullable', 'string', 'max:500'],
            ],
            'feries' => [
                'date' => ['required', 'date'],
                'label_fr' => ['required', 'string', 'max:120'],
                'label_en' => ['required', 'string', 'max:120'],
                'is_agency_closed' => ['nullable', 'boolean'],
                'is_price_surcharged' => ['nullable', 'boolean'],
            ],
            default => [],
        };
    }
}
