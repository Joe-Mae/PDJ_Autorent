<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DocumentType;

/**
 * Règles du dépôt d'une pièce, communes aux deux parcours.
 *
 * Le type détermine les règles du fichier : identité et selfie passent par
 * une image (le cadrage compte), les autres acceptent aussi le PDF. Le choix
 * se lit sur la saisie brute pour que rules() reste autoportant.
 */
trait ValidatesDocumentDeposit
{
    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        $requiresImage = in_array($this->input('type'), [
            DocumentType::IdentityCard->value,
            DocumentType::Selfie->value,
        ], true);

        return [
            'type' => ['required', 'in:'.implode(',', array_map(
                fn (DocumentType $type) => $type->value,
                DocumentType::acceptedOnline(),
            ))],
            'file' => $requiresImage
                ? ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']
                : ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
