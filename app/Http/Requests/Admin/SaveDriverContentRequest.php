<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Textes du bloc chauffeur de l'accueil.
 *
 * Chaque champ existe en français et en anglais. Un champ vidé restaure le
 * texte livré avec l'application : la vue retombe alors sur les traductions,
 * et l'agence n'est jamais obligée de tout réécrire pour corriger un mot.
 */
class SaveDriverContentRequest extends FormRequest
{
    /** Champs éditables, avec leur longueur maximale. */
    public const FIELDS = [
        'premium_badge' => 40,
        'title' => 120,
        'intro' => 500,
        'point1' => 300,
        'point2' => 300,
        'point3' => 300,
        'point4' => 300,
        'cta' => 60,
        'page_intro' => 400,
    ];

    /**
     * Les rôles sont vérifiés par les middlewares de route : toute requête
     * validée ici vient déjà d'un accès autorisé.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [];

        foreach (self::FIELDS as $field => $max) {
            foreach (['fr', 'en'] as $locale) {
                $rules[$field.'_'.$locale] = ['nullable', 'string', 'max:'.$max];
            }
        }

        return $rules;
    }
}
