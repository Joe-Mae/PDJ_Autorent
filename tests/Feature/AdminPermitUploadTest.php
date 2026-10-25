<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Dépôt du permis depuis le dossier de réservation.
 *
 * Le permis est contrôlé en agence : n'importe quel employé peut joindre la
 * photo au dossier, sans que cela fasse démarrer la location. La remise des
 * clés reste le geste qui passe la réservation en « en cours ».
 */
class AdminPermitUploadTest extends TestCase
{
    public function test_un_employe_joint_le_permis_au_dossier(): void
    {
        Storage::fake('documents');

        $booking = Booking::factory()->confirmed()->create();
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('admin.bookings.permit.store', $booking), [
                'permit' => UploadedFile::fake()->image('permis.jpg'),
                'permit_consent' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $booking->refresh();

        $this->assertTrue($booking->hasDriverLicenseCopy());
        $this->assertNotNull($booking->driver_license_copy_consent_at);
        $this->assertSame(BookingStatus::Confirmee, $booking->status);

        $document = $booking->documents()->where('type', DocumentType::DriverLicense->value)->firstOrFail();

        $this->assertSame(DocumentStatus::Conforme, $document->status);
        $this->assertSame($agent->id, $document->reviewed_by);
        Storage::disk('documents')->assertExists($document->path);
    }

    public function test_un_second_envoi_remplace_le_permis_precedent(): void
    {
        Storage::fake('documents');

        $booking = Booking::factory()->confirmed()->create();
        $agent = $this->staff();

        foreach (['permis-1.jpg', 'permis-2.jpg'] as $filename) {
            $this->actingAs($agent)->post(route('admin.bookings.permit.store', $booking), [
                'permit' => UploadedFile::fake()->image($filename),
                'permit_consent' => '1',
            ]);
        }

        $this->assertSame(1, $booking->documents()->where('type', DocumentType::DriverLicense->value)->count());
    }

    public function test_la_photo_du_permis_est_obligatoire_dans_le_dossier(): void
    {
        Storage::fake('documents');

        $booking = Booking::factory()->confirmed()->create();

        $this->actingAs($this->staff())
            ->from(route('admin.bookings.show', $booking))
            ->post(route('admin.bookings.permit.store', $booking), ['permit_consent' => '1'])
            ->assertSessionHasErrors('permit');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_le_consentement_du_client_est_obligatoire(): void
    {
        Storage::fake('documents');

        $booking = Booking::factory()->confirmed()->create();

        $this->actingAs($this->staff())
            ->from(route('admin.bookings.show', $booking))
            ->post(route('admin.bookings.permit.store', $booking), [
                'permit' => UploadedFile::fake()->image('permis.jpg'),
            ])
            ->assertSessionHasErrors('permit_consent');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_une_reservation_avec_chauffeur_n_accepte_pas_de_permis(): void
    {
        Storage::fake('documents');

        $booking = Booking::factory()->withDriver()->confirmed()->create();

        $this->actingAs($this->staff())
            ->post(route('admin.bookings.permit.store', $booking), [
                'permit' => UploadedFile::fake()->image('permis.jpg'),
                'permit_consent' => '1',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_la_page_reservation_montre_la_piece_du_client_sans_bloc_de_verification(): void
    {
        Storage::fake('documents');
        Storage::disk('documents')->buildTemporaryUrlsUsing(fn (): string => 'https://example.test/document');

        $booking = Booking::factory()->confirmed()->create();
        $booking->documents()->create([
            'type' => DocumentType::IdentityCard,
            'status' => DocumentStatus::Conforme,
            'disk' => 'documents',
            'path' => 'reservations/'.$booking->reference.'/piece.jpg',
            'original_filename' => 'piece.jpg',
            'mime_type' => 'image/jpeg',
            'size_kb' => 120,
        ]);

        $this->actingAs($this->staff())
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee(__('document.type.piece_identite'))
            ->assertSee(__('admin.permit_section'))
            ->assertSee('https://example.test/document', false)
            ->assertDontSee('Vérification des documents')
            ->assertDontSee(__('admin.identity_card_missing'));
    }
}
