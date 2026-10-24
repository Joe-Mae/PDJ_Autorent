<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\SettingsService;
use App\Support\BookingPeriod;
use Tests\TestCase;

/**
 * Bon de réservation PDF : la mention et le nom du fichier.
 *
 * Le bon part à l'employeur et au guichet : il doit dire au client quoi
 * apporter (un client avec chauffeur qui court chercher son permis pour rien,
 * un client sans chauffeur qui l'oublie et se fait renvoyer), et son nom de
 * fichier doit porter la marque sans qu'aucun code ne la répète en dur.
 */
class BookingReceiptTest extends TestCase
{
    public function test_le_nom_du_fichier_est_la_reference_de_la_reservation(): void
    {
        $booking = $this->aBooking();

        $disposition = $this->get(route('booking.receipt', [
            'locale' => 'fr',
            'booking' => $booking,
        ]))->assertOk()->assertHeader('content-type', 'application/pdf')
            ->headers->get('content-disposition');

        $this->assertNotNull($disposition);
        $this->assertStringContainsString($booking->reference.'.pdf', $disposition);
        // Le préfixe ne doit jamais être doublé : la référence le porte déjà.
        $this->assertStringNotContainsString('PDJ-PDJ-', $disposition);
    }

    /** Le nom du fichier suit la marque : aucun « PDJ » codé en dur côté fichier. */
    public function test_le_nom_du_fichier_ne_contient_pas_de_marque_ecrite_en_dur(): void
    {
        $booking = $this->aBooking(['reference' => 'AGENCE-42ABC1']);

        $disposition = $this->get(route('booking.receipt', [
            'locale' => 'fr',
            'booking' => $booking,
        ]))->assertOk()->headers->get('content-disposition');

        $this->assertStringContainsString('AGENCE-42ABC1.pdf', (string) $disposition);
        $this->assertStringNotContainsString('PDJ', (string) $disposition);
    }

    public function test_le_bon_dit_de_apporter_son_permis_sans_chauffeur(): void
    {
        $html = $this->renderReceipt($this->aBooking(['with_driver' => false]));

        // Fragments sans apostrophe : Blade échappe l'apostrophe (&#039;).
        $this->assertStringContainsString('Présentez-vous avec votre permis de conduire', $html);
        $this->assertStringNotContainsString('pas besoin de permis', $html);
    }

    public function test_le_bon_ne_demande_pas_de_permis_avec_chauffeur(): void
    {
        $html = $this->renderReceipt($this->aBooking([
            'with_driver' => true,
            'driver_id' => $this->driver()->id,
        ]));

        $this->assertStringContainsString('pas besoin de permis', $html);
        $this->assertStringNotContainsString('Présentez-vous avec votre permis de conduire', $html);
    }

    /** Rend la vue du bon : dompdf compresse le texte, on ne peut pas le relire. */
    private function renderReceipt(Booking $booking): string
    {
        return view('booking.receipt', [
            'booking' => $booking->load(['vehicle.category', 'user', 'payments']),
            'period' => BookingPeriod::fromDates(
                $booking->starts_at->toImmutable(),
                $booking->ends_at->toImmutable(),
            ),
            'settings' => app(SettingsService::class),
        ])->render();
    }

    private function aBooking(array $attributes = []): Booking
    {
        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(),
            'user' => $this->customer(),
            'with_driver' => false,
        ]);

        $booking->forceFill($attributes)->save();

        return $booking->refresh();
    }
}
