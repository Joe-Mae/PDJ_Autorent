<?php

namespace Tests\Unit;

use App\Services\ReferenceGenerator;
use PHPUnit\Framework\TestCase;

/**
 * La référence est dictée au téléphone au guichet. Il ne faut donc pas qu'elle
 * contienne de paires qui se confondent à l'oral : « O » et « 0 », « I » et
 * « 1 », « S » et « 5 ».
 */
class ReferenceGeneratorTest extends TestCase
{
    public function test_produit_une_reference_bien_formee(): void
    {
        $generator = new ReferenceGenerator(null);

        for ($i = 0; $i < 50; $i++) {
            $reference = $generator->generate(fn () => false);

            $this->assertMatchesRegularExpression('/^PDJ-[A-Z0-9]{6}$/', $reference);
            $this->assertTrue($generator->isValid($reference));
        }
    }

    /** Sans réglage (test unitaire sans base), c'est la marque par défaut qui sert. */
    public function test_sans_reglage_le_prefixe_est_la_marque_par_defaut(): void
    {
        $this->assertSame(ReferenceGenerator::DEFAULT_PREFIX, (new ReferenceGenerator(null))->prefix());
    }

    public function test_n_utilise_aucun_caracterre_ambigu(): void
    {
        $generator = new ReferenceGenerator(null);

        for ($i = 0; $i < 200; $i++) {
            $reference = $generator->generate(fn () => false);

            foreach (['0', 'O', '1', 'I', 'L', '5', 'S', '2', 'Z', '8', 'B'] as $ambigu) {
                $this->assertStringNotContainsString(
                    $ambigu,
                    substr($reference, 4),
                    "La référence {$reference} contient le caractère ambigu « {$ambigu} »."
                );
            }
        }
    }

    public function test_rejette_les_references_mal_ecrites(): void
    {
        $generator = new ReferenceGenerator(null);

        $this->assertFalse($generator->isValid('PDJ-ABC'));
        $this->assertFalse($generator->isValid('PDJ-ABCDEFG'));
        $this->assertFalse($generator->isValid('ABC-ABCDEF'));
        $this->assertFalse($generator->isValid('PDJ-ABC0EF'), 'Un zéro ne fait pas partie de l\'alphabet.');
    }

    public function test_produit_des_references_differentes(): void
    {
        $generator = new ReferenceGenerator(null);
        $references = [];

        for ($i = 0; $i < 100; $i++) {
            $references[] = $generator->generate(fn () => false);
        }

        $this->assertGreaterThan(90, count(array_unique($references)));
    }

    public function test_repete_tant_que_la_reference_existe_deja(): void
    {
        $generator = new ReferenceGenerator(null);
        $tentatives = 0;

        $reference = $generator->generate(function (string $candidate) use (&$tentatives) {
            $tentatives++;

            // Les deux premières sont « prises », la troisième est libre.
            return $tentatives < 3;
        });

        $this->assertSame(3, $tentatives);
        $this->assertTrue($generator->isValid($reference));
    }
}
