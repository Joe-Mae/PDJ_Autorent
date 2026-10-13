<?php

/*
 * Ajoute un véhicule à la flotte, avec sa photo si le fichier est présent.
 *
 * Le nom du modèle suit la photo, pas un nom supposé : sur un site de
 * location, la fiche doit promettre ce que le client verra au guichet.
 *
 * Usage :
 *   php add-lamborghini.php [chemin/vers/la/photo.jpg]
 *
 * Sans argument, le véhicule est créé sans photo — le formulaire
 * d'administration permet de l'ajouter ensuite depuis /admin/vehicules.
 */

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

$cheminPhoto = $argv[1] ?? null;

$existant = Vehicle::query()->where('brand', 'Lamborghini')->where('model', 'Huracán')->first();

if ($existant !== null) {
    echo "Déjà dans la flotte : #{$existant->id} {$existant->displayName()}\n";

    if ($cheminPhoto === null) {
        return;
    }
} else {
    $luxe = VehicleCategory::query()->where('slug', 'luxe')->firstOrFail();

    $existant = Vehicle::query()->create([
        'category_id' => $luxe->id,
        'brand' => 'Lamborghini',
        'model' => 'Huracán',
        'plate' => 'TG-9101-HU',
        'year' => 2024,
        'transmission' => 'automatique',
        'fuel' => 'essence',
        'seats' => 2,
        'doors' => 2,
        'bags' => 1,
        'air_conditioning' => true,
        'features' => ['gps', 'bluetooth', 'camera_rear', 'reversing_camera', 'leather', 'carbon'],
        'description_fr' => 'Huracán, propulsion arrière, boîte à double embrayage. '
            .'Location avec chauffeur recommandé : la conduite en ville demande de l\'expérience.',
        'description_en' => 'Huracán, rear-wheel drive, dual-clutch gearbox. '
            .'Chauffeur recommended: city driving takes some practice.',
        'mileage' => 12000,
        'next_technical_control_at' => now()->addMonths(6)->toDateString(),
        'fuel_level' => 'full',
        'status' => VehicleStatus::Disponible,
        'is_active' => true,
    ]);

    echo "Créé : #{$existant->id} {$existant->displayName()} — {$existant->plate}\n";
    echo 'Catégorie : '.$luxe->name().' ('.number_format($luxe->daily_price, 0, ' ', ' ')." F CFA / jour)\n";
}

if ($cheminPhoto !== null) {
    if (! is_file($cheminPhoto)) {
        echo "ERREUR : fichier introuvable — {$cheminPhoto}\n";
        exit(1);
    }

    $existant->clearMediaCollection(Vehicle::MEDIA_GALLERY);

    $existant->addMedia($cheminPhoto)
        ->preservingOriginal()
        ->usingFileName(
            Str::slug($existant->brand.'-'.$existant->model).'.'
            .pathinfo($cheminPhoto, PATHINFO_EXTENSION)
        )
        ->toMediaCollection(Vehicle::MEDIA_GALLERY, 'fleet');

    $media = $existant->getMedia(Vehicle::MEDIA_GALLERY)->first();

    echo 'Photo attachée : '.$media->file_name.' ('.number_format($media->size / 1024)." Ko)\n";
    echo 'URL           : '.$media->getUrl()."\n";
}
