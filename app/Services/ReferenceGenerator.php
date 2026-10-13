<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Références courtes et dictables au téléphone : « PDJ-8F3K2A ».
 *
 * On exclut les caractères qui se confondent à la lecture (0/O, 1/I/L, 5/S, 2/Z)
 * parce qu'une référence lue à voix haute au guichet doit être sans ambiguïté.
 */
class ReferenceGenerator
{
    public const PREFIX = 'PDJ-';

    public const LENGTH = 6;

    private const ALPHABET = '34679ACDEFHJKMNPQRTUVWXY';

    /**
     * @param  (callable(string): bool)|null  $exists  test d'unicité ; injecté
     *                                                 pour que la génération soit testable sans base de données.
     */
    public function generate(?callable $exists = null): string
    {
        $exists ??= fn (string $reference) => Booking::query()->where('reference', $reference)->exists();

        do {
            $reference = self::PREFIX.$this->randomToken();
        } while ($exists($reference));

        return $reference;
    }

    public function isValid(string $reference): bool
    {
        return (bool) preg_match('/^PDJ-['.self::ALPHABET.']{'.self::LENGTH.'}$/', $reference);
    }

    /** Jeton brut, sans préfixe ni garantie d'unicité. */
    public function randomToken(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $token = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $token .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $token;
    }
}
