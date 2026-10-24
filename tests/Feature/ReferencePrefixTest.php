<?php

namespace Tests\Feature;

use App\Services\ReferenceGenerator;
use Tests\TestCase;

/**
 * Préfixe des références, réglable par l'agence.
 *
 * La référence est la seule chose que le client retient et qu'on dicte au
 * téléphone : elle porte la marque. Un blanc-marque change le réglage
 * `booking.reference_prefix`, jamais le code — et les références déjà
 * émises, elles, ne bougent plus, puisqu'elles sont stockées.
 */
class ReferencePrefixTest extends TestCase
{
    public function test_le_prefixe_par_defaut_est_la_marque_de_demonstration(): void
    {
        $generator = app(ReferenceGenerator::class);

        $this->assertSame('PDJ-', $generator->prefix());
        $this->assertMatchesRegularExpression('/^PDJ-[A-Z0-9]{6}$/', $generator->generate(fn () => false));
    }

    public function test_changer_le_reglage_change_les_references_generées(): void
    {
        $this->settings()->put('booking.reference_prefix', 'acme');
        $generator = app(ReferenceGenerator::class);

        $this->assertSame('ACME-', $generator->prefix());

        $reference = $generator->generate(fn () => false);

        $this->assertStringStartsWith('ACME-', $reference);
        $this->assertTrue($generator->isValid($reference));
        $this->assertFalse($generator->isValid('PDJ-ABCDEF'), 'Une référence de l ancienne marque n est plus valide.');
    }

    /** Un réglage saisi trop vite reste exploitable : tiret final garanti. */
    public function test_le_prefixe_est_normalise(): void
    {
        $this->settings()->put('booking.reference_prefix', 'acme');

        $this->assertSame('ACME-', app(ReferenceGenerator::class)->prefix());
    }

    public function test_un_prefixe_vide_retombe_sur_la_marque(): void
    {
        $this->settings()->put('booking.reference_prefix', '   ');

        $this->assertSame('PDJ-', app(ReferenceGenerator::class)->prefix());
    }

    /** Les références émises sont stockées : un changement de marque ne les réécrit pas. */
    public function test_les_references_existantes_ne_changent_pas(): void
    {
        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(),
            'user' => $this->customer(),
            'with_driver' => false,
        ]);

        $this->assertStringStartsWith('PDJ-', $booking->reference);

        $this->settings()->put('booking.reference_prefix', 'ACME');

        $this->assertSame($booking->reference, $booking->fresh()->reference);
    }
}
