<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Références courtes et dictables au téléphone : « PDJ-8F3K2A ».
 *
 * On exclut les caractères qui se confondent à la lecture (0/O, 1/I/L, 5/S, 2/Z)
 * parce qu'une référence lue à voix haute au guichet doit être sans ambiguïté.
 *
 * Le préfixe vient du réglage `booking.reference_prefix` : un blanc-marque
 * change son nom ici, pas dans le code. Il est normalisé (majuscules, tiret
 * final) parce qu'une référence doit rester dictable quel que soit l'effort
 * fait sur la saisie du réglage.
 */
class ReferenceGenerator
{
    /** Repli : la marque de démonstration, tant qu'aucun réglage n'existe. */
    public const DEFAULT_PREFIX = 'PDJ-';

    public const LENGTH = 6;

    private const ALPHABET = '34679ACDEFHJKMNPQRTUVWXY';

    /**
     * Injecté en nullable : les tests unitaires du générateur tournent sans
     * base de données, donc sans SettingsService (« null » explicite).
     */
    public function __construct(private readonly ?SettingsService $settings) {}

    /** Préfixe courant, normalisé : « acme » → « ACME- ». */
    public function prefix(): string
    {
        $prefix = strtoupper(trim($this->settings?->string('booking.reference_prefix') ?? self::DEFAULT_PREFIX));

        if ($prefix === '') {
            return self::DEFAULT_PREFIX;
        }

        return str_ends_with($prefix, '-') ? $prefix : $prefix.'-';
    }

    /**
     * @param  (callable(string): bool)|null  $exists  test d'unicité ; injecté
     *                                                 pour que la génération soit testable sans base de données.
     */
    public function generate(?callable $exists = null): string
    {
        $exists ??= fn (string $reference) => Booking::query()->where('reference', $reference)->exists();

        do {
            $reference = $this->prefix().$this->randomToken();
        } while ($exists($reference));

        return $reference;
    }

    public function isValid(string $reference): bool
    {
        return (bool) preg_match(
            '/^'.preg_quote($this->prefix(), '/').'['.self::ALPHABET.']{'.self::LENGTH.'}$/',
            $reference,
        );
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
