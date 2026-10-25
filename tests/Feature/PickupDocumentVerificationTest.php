<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contrôle des documents au guichet.
 *
 * L'identité est vérifiée à l'arrivée. Le permis, lui, est contrôlé au dernier
 * moment, juste avant la remise des clés : la caissière le photographie et son
 * enregistrement fait passer la réservation en « en cours ».
 */
class PickupDocumentVerificationTest extends TestCase
{
    public function test_l_agent_enregistre_le_controle_d_identite(): void
    {
        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->get(route('agent.pickup.documents', $booking))
            ->assertOk()
            ->assertSee(__('agent.identity_check'));

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck())
            ->assertRedirect(route('agent.pickup.condition', $booking));

        $booking->refresh();

        $this->assertSame(DocumentVerificationStatus::EnAttente, $booking->document_verification_status);
        $this->assertTrue($booking->hasVerifiedIdentity());
        $this->assertFalse($booking->isReadyForHandover());
        $this->assertNotNull($booking->documents_verified_at);
        $this->assertSame($agent->id, $booking->documents_verified_by);

        $this->actingAs($agent)
            ->get(route('agent.pickup.keys', $booking))
            ->assertOk()
            ->assertSee(__('agent.license_photo'))
            ->assertSee(__('agent.verify_license_and_give_keys'));
    }

    public function test_le_permis_photographie_pendant_la_remise_fait_passer_la_reservation_en_cours(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck());

        $this->actingAs($agent)
            ->post(route('agent.pickup.complete', $booking), $this->compliantLicense($booking) + [
                'license_copy_consent' => '1',
                'license_copy' => UploadedFile::fake()->image('permis.jpg'),
            ])
            ->assertRedirect(route('agent.pickup.index'));

        $booking->refresh();

        $this->assertSame(BookingStatus::EnCours, $booking->status);
        $this->assertSame(VehicleStatus::EnLocation, $booking->vehicle->refresh()->status);
        $this->assertSame(DocumentVerificationStatus::Conforme, $booking->document_verification_status);
        $this->assertNotNull($booking->driver_license_copy_consent_at);

        $copy = $booking->documents()->where('type', 'permis')->firstOrFail();
        $this->assertSame('conforme', $copy->status->value);
        Storage::disk('documents')->assertExists($copy->path);
    }

    public function test_un_permis_non_valide_pendant_toute_la_location_bloque_la_remise(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck());

        $license = $this->compliantLicense($booking);
        $license['driver_license_expires_at'] = $booking->ends_at->copy()->subDay()->toDateString();

        $this->actingAs($agent)
            ->from(route('agent.pickup.keys', $booking))
            ->post(route('agent.pickup.complete', $booking), $license + [
                'license_copy_consent' => '1',
                'license_copy' => UploadedFile::fake()->image('permis.jpg'),
            ])
            ->assertRedirect(route('agent.pickup.keys', $booking))
            ->assertSessionHas('error');

        $booking->refresh();

        $this->assertSame(BookingStatus::Confirmee, $booking->status);
        $this->assertSame(DocumentVerificationStatus::NonConforme, $booking->document_verification_status);
        $this->assertFalse($booking->isReadyForHandover());
        $this->assertDatabaseMissing('documents', ['booking_id' => $booking->id, 'type' => 'permis']);
    }

    public function test_la_photo_du_permis_est_obligatoire_avant_de_remettre_les_cles(): void
    {
        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck());

        $this->actingAs($agent)
            ->from(route('agent.pickup.keys', $booking))
            ->post(route('agent.pickup.complete', $booking), $this->compliantLicense($booking))
            ->assertSessionHasErrors('license_copy');

        $this->assertSame(BookingStatus::Confirmee, $booking->refresh()->status);
    }

    public function test_un_controle_avec_chauffeur_ne_demande_pas_le_permis(): void
    {
        $booking = Booking::factory()->withDriver()->confirmed()->create();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck())
            ->assertRedirect(route('agent.pickup.condition', $booking));

        $this->actingAs($agent)
            ->post(route('agent.pickup.complete', $booking))
            ->assertRedirect(route('agent.pickup.index'));

        $booking->refresh();

        $this->assertSame(DocumentVerificationStatus::NonRequis, $booking->document_verification_status);
        $this->assertFalse($booking->driver_license_original_verified);
        $this->assertSame(BookingStatus::EnCours, $booking->status);
    }

    public function test_photo_du_permis_conservee_uniquement_avec_consentement(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->identityCheck());

        $this->actingAs($agent)
            ->from(route('agent.pickup.keys', $booking))
            ->post(route('agent.pickup.complete', $booking), $this->compliantLicense($booking) + [
                'license_copy' => UploadedFile::fake()->image('permis.jpg'),
            ])
            ->assertSessionHasErrors('license_copy_consent');

        $this->assertDatabaseMissing('documents', ['booking_id' => $booking->id, 'type' => 'permis']);
        $this->assertSame(BookingStatus::Confirmee, $booking->refresh()->status);
    }

    private function confirmedBooking(): Booking
    {
        return $this->bookings()->confirm(
            $this->bookings()->create([
                'vehicle' => $this->vehicle(),
                'period' => $this->period(),
                'user' => null,
                'with_driver' => false,
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]),
            $this->staff(),
        );
    }

    /** @return array<string, string> */
    private function identityCheck(): array
    {
        return [
            'identity_original_verified' => '1',
            'identity_name_matches' => '1',
            'customer_age_eligible' => '1',
        ];
    }

    /** @return array<string, string> */
    private function compliantLicense(Booking $booking): array
    {
        return [
            'driver_license_original_verified' => '1',
            'driver_license_category_authorized' => '1',
            'driver_license_category' => 'B',
            'driver_license_issued_at' => today()->subYears(2)->toDateString(),
            'driver_license_expires_at' => $booking->ends_at->copy()->addDay()->toDateString(),
        ];
    }
}
