<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création ou mise à jour d'un véhicule depuis l'administration.
 */
class SaveVehicleRequest extends FormRequest
{
    /** Nombre de photos acceptées d'un coup. */
    private const MAX_PHOTOS = 10;

    /**
     * Taille maximale d'une photo, en kilo-octets.
     *
     * Volontairement inférieure à upload_max_filesize (2 M par défaut) : on
     * refuse le fichier avec un message lisible, plutôt que de le laisser
     * disparaître sans explication.
     */
    private const MAX_PHOTO_KILOS = 2048;

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
            'category_id' => ['required', 'exists:vehicle_categories,id'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($this->route('vehicle')?->id)],
            'year' => ['required', 'integer', 'between:2000,'.(now()->year + 1)],
            'transmission' => ['required', 'in:manuelle,automatique'],
            'fuel' => ['required', 'in:essence,diesel,hybride,electrique'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'doors' => ['required', 'integer', 'between:2,6'],
            'bags' => ['required', 'integer', 'between:0,9'],
            'air_conditioning' => ['nullable', 'boolean'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'next_technical_control_at' => ['nullable', 'date'],
            'description_fr' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],

            // Le format et la taille sont vérifiés ici, et non laissés au
            // serveur. Sans ces règles, une photo de téléphone trop lourde est
            // refusée par PHP sans qu'aucun message n'apparaisse : l'agent
            // voit simplement « rien ne s'est enregistré ».
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_PHOTO_KILOS,
            ],
            'main_photo_id' => ['nullable', 'integer'],
            'main_photo_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'plate.unique' => __('admin.plate_taken'),
            'photos.max' => __('admin.photos_too_many', ['max' => self::MAX_PHOTOS]),
            'photos.*.image' => __('admin.photo_not_image'),
            'photos.*.mimes' => __('admin.photo_bad_format'),
            'photos.*.max' => __('admin.photo_too_heavy', ['max' => self::MAX_PHOTO_KILOS]),
        ];
    }
}
