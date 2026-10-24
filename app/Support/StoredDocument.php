<?php

namespace App\Support;

/**
 * Résultat d'un dépôt de pièce : tout ce qu'il faut pour créer la ligne
 * `documents` sans que l'appelant retouche le fichier.
 */
final readonly class StoredDocument
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeKb,
    ) {}
}
