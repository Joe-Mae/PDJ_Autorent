<?php

namespace Tests\Feature;

use App\Enums\DocumentVerificationStatus;
use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PickupDocumentVerificationTest extends TestCase
{
    public function test_agent_enregistre_un_controle_conforme_avant_la_remise(): void
    {
        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.documents.store', $booking), $this->compliantDocuments($booking))
            ->assertRedirect(route('agent.pickup.condition', $booking));

        $booking->refresh();

        $this->assertSame(DocumentVerificationStatus::Conforme, $booking->document_verification_status);
        $this->assertTrue($booking->isReadyForHandover());
        $this->assertNotNull($booking->documents_verified_at);
        $this->assertSame($agent->id, $booking->documents_verified_by);
    }

    public function test_un_permis_non_valide_pendant_toute_la_location_bloque_la_remise(): void
    {
        $booking = $this->confirmedBooking();
        $documents = $this->compliantDocuments($booking);
        $documents['driver_license_expires_at'] = $booking->ends_at->copy()->subDay()->toDateString();

        $this->actingAs($this->staff())
            ->from(route('agent.pickup.documents', $booking))
            ->post(route('agent.pickup.documents.store', $booking), $documents)
            ->assertRedirect(route('agent.pickup.documents', $booking))
            ->assertSessionHas('error', __('agent.document_check_failed'));

        $this->assertSame(
            DocumentVerificationStatus::NonConforme,
            $booking->refresh()->document_verification_status,
        );
        $this->assertFalse($booking->isReadyForHandover());
    }

    public function test_un_controle_avec_chauffeur_ne_demande_pas_le_permis(): void
    {
        $booking = Booking::factory()->withDriver()->confirmed()->create();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.documents.store', $booking), [
                'identity_original_verified' => '1',
                'identity_name_matches' => '1',
                'customer_age_eligible' => '1',
            ])
            ->assertRedirect(route('agent.pickup.condition', $booking));

        $booking->refresh();

        $this->assertSame(DocumentVerificationStatus::NonRequis, $booking->document_verification_status);
        $this->assertFalse($booking->driver_license_original_verified);
        $this->assertTrue($booking->isReadyForHandover());
    }

    public function test_copie_du_permis_conservee_uniquement_avec_consentement(): void
    {
        Storage::fake('documents');

        $booking = $this->confirmedBooking();
        $documents = $this->compliantDocuments($booking);

        $this->actingAs($this->staff())
            ->from(route('agent.pickup.documents', $booking))
            ->post(route('agent.pickup.documents.store', $booking), $documents + [
                'license_copy' => UploadedFile::fake()->image('licence.jpg'),
            ])
            ->assertSessionHasErrors('license_copy_consent');

        $this->assertDatabaseMissing('documents', ['booking_id' => $booking->id, 'type' => 'permis']);

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.documents.store', $booking), $documents + [
                'license_copy_consent' => '1',
                'license_copy' => UploadedFile::fake()->image('licence.jpg'),
            ])
            ->assertRedirect(route('agent.pickup.condition', $booking));

        $copy = $booking->documents()->where('type', 'permis')->firstOrFail();
        $this->assertNotNull($booking->refresh()->driver_license_copy_consent_at);
        Storage::disk('documents')->assertExists($copy->path);
    }

    private function confirmedBooking(bool $withDriver = false): Booking
    {
        if ($withDriver) {
            return Booking::factory()->withDriver()->confirmed()->create([
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]);
        }

        return $this->bookings()->confirm(
            $this->bookings()->create([
                'vehicle' => $this->vehicle(),
                'period' => $this->period(),
                'user' => null,
                'with_driver' => $withDriver,
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]),
            $this->staff(),
        );
    }

    /** @return array<string, string> */
    private function compliantDocuments(Booking $booking): array
    {
        return [
            'identity_original_verified' => '1',
            'identity_name_matches' => '1',
            'customer_age_eligible' => '1',
            'driver_license_original_verified' => '1',
            'driver_license_category_authorized' => '1',
            'driver_license_category' => 'B',
            'driver_license_issued_at' => today()->subYears(2)->toDateString(),
            'driver_license_expires_at' => $booking->ends_at->copy()->addDay()->toDateString(),
        ];
    }
}
