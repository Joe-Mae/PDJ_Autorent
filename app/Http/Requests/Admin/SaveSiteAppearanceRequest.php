<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Enregistrement de l'apparence du site (couleurs, identité, libellés).
 *
 * Les tableaux du diaporama sont normalisés avant validation : un <select>
 * sans option envoie une chaîne vide, qui ne doit pas compter comme un
 * identifiant de véhicule ni comme une photo à retirer.
 */
class SaveSiteAppearanceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $heroVehicleIdsInput = $this->input('hero_vehicle_ids', []);
        if (is_array($heroVehicleIdsInput)) {
            $this->merge([
                'hero_vehicle_ids' => array_values(array_filter(
                    $heroVehicleIdsInput,
                    fn ($id) => $id !== null && $id !== '',
                )),
            ]);
        }

        $removeHeroImagesInput = $this->input('remove_hero_images', []);
        if (is_array($removeHeroImagesInput)) {
            $this->merge([
                'remove_hero_images' => array_values(array_filter(
                    $removeHeroImagesInput,
                    fn ($path) => $path !== null && $path !== '',
                )),
            ]);
        }
    }

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
            'hero_vehicle_ids' => ['nullable', 'array', 'max:4'],
            'hero_vehicle_ids.*' => ['integer', 'distinct', Rule::exists('vehicles', 'id')],
            'hero_images' => ['nullable', 'array', 'max:4'],
            'hero_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_hero_images' => ['nullable', 'array'],
            'remove_hero_images.*' => ['string', 'distinct'],
            'brand_color' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'accent_color' => ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'brand_mark' => ['nullable', 'string', 'max:8'],
            'brand_label' => ['required', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'hero_title_fr' => ['required', 'string', 'max:150'],
            'hero_title_en' => ['nullable', 'string', 'max:150'],
            'hero_subtitle_fr' => ['required', 'string', 'max:500'],
            'hero_subtitle_en' => ['nullable', 'string', 'max:500'],
            'hero_eyebrow_fr' => ['required', 'string', 'max:100'],
            'hero_eyebrow_en' => ['required', 'string', 'max:100'],
            'footer_tagline_fr' => ['required', 'string', 'max:180'],
            'footer_tagline_en' => ['required', 'string', 'max:180'],
            'nav_home_fr' => ['required', 'string', 'max:40'],
            'nav_home_en' => ['required', 'string', 'max:40'],
            'nav_fleet_fr' => ['required', 'string', 'max:40'],
            'nav_fleet_en' => ['required', 'string', 'max:40'],
            'nav_driver_fr' => ['required', 'string', 'max:40'],
            'nav_driver_en' => ['required', 'string', 'max:40'],
            'nav_faq_fr' => ['required', 'string', 'max:50'],
            'nav_faq_en' => ['required', 'string', 'max:50'],
            'nav_contact_fr' => ['required', 'string', 'max:40'],
            'nav_contact_en' => ['required', 'string', 'max:40'],
            'nav_lookup_fr' => ['required', 'string', 'max:40'],
            'nav_lookup_en' => ['required', 'string', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'hero_images.max' => 'Vous pouvez importer quatre photos au maximum à la fois.',
            'hero_images.*.image' => 'Chaque fichier du diaporama doit être une image valide.',
            'hero_images.*.mimes' => 'Formats acceptés pour le diaporama : JPG, PNG ou WebP.',
            'hero_images.*.max' => 'Chaque photo du diaporama ne doit pas dépasser 2 Mo.',
            'logo.image' => 'Le logo doit être une image valide.',
            'logo.mimes' => 'Formats acceptés pour le logo : JPG, PNG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ];
    }
}
