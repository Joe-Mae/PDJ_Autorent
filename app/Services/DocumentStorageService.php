<?php

namespace App\Services;

use App\Support\StoredDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Dépôt d'une pièce client sur le disque privé.
 *
 * Deux règles non négociables : le nom du fichier stocké est aléatoire (le nom
 * d'origine ne doit jamais apparaître dans l'URL), et les images sont
 * ré-encodées. Le ré-encodage supprime les métadonnées EXIF — un téléphone y
 * glisse la date, le modèle, parfois la position GPS — et neutralise les
 * charges utiles cachées dans un JPEG.
 */
class DocumentStorageService
{
    public const DISK = 'documents';

    /** @var array<string, string> Type MIME réel → extension stockée. */
    private const IMAGE_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * @param  string  $directory  Dossier de rattachement (référence de réservation).
     * @param  string  $attribute  Champ de formulaire, pour cibler l'erreur.
     */
    public function store(UploadedFile $file, string $directory, string $attribute = 'file'): StoredDocument
    {
        $mimeType = $file->getMimeType();
        $disk = Storage::disk(self::DISK);

        if (array_key_exists($mimeType, self::IMAGE_EXTENSIONS)) {
            [$contents, $mimeType] = $this->stripMetadata($file, $mimeType, $attribute);
            $extension = self::IMAGE_EXTENSIONS[$mimeType];
        } elseif ($mimeType === 'application/pdf') {
            $contents = (string) $file->get();
            $extension = 'pdf';
        } else {
            // Le type réel du fichier prime sur son extension déclarée.
            throw ValidationException::withMessages([
                $attribute => __('document.unreadable'),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;
        $disk->put($path, $contents);

        return new StoredDocument(
            disk: self::DISK,
            path: $path,
            originalFilename: Str::limit($file->getClientOriginalName(), 120, ''),
            mimeType: $mimeType,
            sizeKb: max(1, (int) ceil(strlen($contents) / 1024)),
        );
    }

    /**
     * Décode puis ré-encode l'image : les pixels survivent, les métadonnées non.
     *
     * @return array{0: string, 1: string} Contenu binaire et type MIME résultant.
     */
    private function stripMetadata(UploadedFile $file, string $mimeType, string $attribute): array
    {
        try {
            $image = ImageManager::gd()->read($file->getRealPath());
            $this->applyExifRotation($image, $file, $mimeType);

            // Une photo de téléphone fait 12 Mpx : inutile de garder cette
            // définition pour relire un numéro de carte d'identité.
            $image->scaleDown(width: 2000);

            if ($mimeType === 'image/png') {
                return [(string) $image->toPng(), 'image/png'];
            }

            return [(string) $image->toJpeg(90), 'image/jpeg'];
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $attribute => __('document.unreadable'),
            ]);
        }
    }

    /**
     * Beaucoup de téléphones enregistrent la photo « à plat » et confient la
     * rotation à une balise EXIF. Comme on supprime ces balises, il faut
     * d'abord coucher les pixels dans le bon sens.
     */
    private function applyExifRotation(ImageInterface $image, UploadedFile $file, string $mimeType): void
    {
        if ($mimeType !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;

        $angle = match ((int) $orientation) {
            3 => 180.0,
            6 => 90.0,
            8 => 270.0,
            default => null,
        };

        if ($angle !== null) {
            $image->rotate($angle);
        }
    }
}
