<?php

namespace Tests\Feature;

use App\Enums\DriverOnlyBookingStatus;
use App\Models\CustomerDocument;
use App\Models\DriverOnlyBooking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DriverOnlyBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
    }

    public static function soloDriverRates(): array
    {
        return [
            'one day' => [1, 15000],
            'three days' => [3, 15000],
            'four days' => [4, 13000],
        ];
    }

    #[DataProvider('soloDriverRates')]
    public function test_public_request_saves_solo_driver_rate_for_duration(int $days, int $dailyRate): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $this->driver();
        $startsAt = today()->addDays(2);
        $endsAt = $startsAt->copy()->addDays($days);

        $response = $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $endsAt->toDateString(),
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ]);

        $booking = DriverOnlyBooking::query()->firstOrFail();
        $response->assertRedirect(route('page.driver', ['locale' => 'fr']));
        $response->assertSessionHas('driver_only_booking_reference', $booking->reference);

        $this->get(route('page.driver', ['locale' => 'fr']))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="identity_document"', false);
        $this->assertSame('Afi Mensah', $booking->customer_name);
        $this->assertSame('+228 90 12 34 56', $booking->phone);
        $this->assertSame($days, $booking->days);
        $this->assertSame($dailyRate, $booking->daily_price);
        $this->assertSame($days * $dailyRate, $booking->price_total);
        $this->assertSame(DriverOnlyBookingStatus::EnAttente, $booking->status);
    }

    public function test_public_request_requires_an_identity_document(): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $this->driver();
        $startsAt = today()->addDays(2);

        $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDay()->toDateString(),
        ])->assertSessionHasErrors('identity_document');

        $this->assertDatabaseCount('driver_only_bookings', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_public_request_rejects_an_invalid_identity_document(): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $this->driver();
        $startsAt = today()->addDays(2);

        $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDay()->toDateString(),
            'identity_document' => UploadedFile::fake()->create('cni.docx', 100, 'application/msword'),
        ])->assertSessionHasErrors('identity_document');

        $this->assertDatabaseCount('driver_only_bookings', 0);
    }

    public function test_submitted_identity_document_is_stored_on_the_private_disk(): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $this->driver();
        $startsAt = today()->addDays(2);

        $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDay()->toDateString(),
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertSessionHasNoErrors();

        $booking = DriverOnlyBooking::query()->firstOrFail();
        $document = $booking->documents()->sole();

        $this->assertSame('piece_identite', $document->type->value);
        $this->assertSame('en_attente', $document->status->value);
        $this->assertNull($document->user_id);
        $this->assertNull($document->booking_id);
        $this->assertSame('image/jpeg', $document->mime_type);
        $this->assertStringStartsWith('driver-only/'.$booking->reference.'/', $document->path);
        Storage::disk('documents')->assertExists($document->path);
    }

    public function test_public_solo_request_is_rejected_when_no_driver_is_available(): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $startsAt = today()->addDays(2);

        $this->post('/fr/chauffeur-seul', [
            'name' => 'Afi Mensah',
            'phone' => '+228 90 12 34 56',
            'du' => $startsAt->toDateString(),
            'au' => $startsAt->copy()->addDay()->toDateString(),
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('driver_only_bookings', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_lookup_finds_a_solo_driver_request(): void
    {
        $booking = DriverOnlyBooking::factory()->create([
            'customer_name' => 'Afi Mensah',
            'price_total' => 45000,
        ]);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => strtolower($booking->reference),
        ])
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee(__('driver.solo_title'))
            ->assertSee('45 000 FCFA');
    }

    public function test_admin_reviews_a_solo_driver_identity_document(): void
    {
        $document = CustomerDocument::factory()->create([
            'user_id' => null,
            'booking_id' => null,
            'driver_only_booking_id' => DriverOnlyBooking::factory()->create()->id,
            'type' => 'piece_identite',
            'status' => 'en_attente',
        ]);

        Storage::disk('documents')->buildTemporaryUrlsUsing(fn (): string => 'https://example.test/document');

        $manager = $this->staff('gestionnaire');

        $this->actingAs($manager)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee($document->driverOnlyBooking->customer_name);

        $this->actingAs($manager)
            ->get(route('admin.documents.show', $document))
            ->assertOk()
            ->assertSee($document->driverOnlyBooking->reference);
    }

    public function test_manager_confirms_and_assigns_a_solo_driver_request(): void
    {
        $manager = $this->staff('gestionnaire');
        $booking = DriverOnlyBooking::factory()->create();
        $driver = $this->driver();

        $this->actingAs($manager)
            ->post(route('admin.driver-only-bookings.confirm', $booking))
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('admin.driver-only-bookings.assign', $booking), ['driver' => $driver->id])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(DriverOnlyBookingStatus::Confirmee, $booking->status);
        $this->assertSame($driver->id, $booking->driver_id);
    }

    public function test_front_desk_cannot_manage_solo_driver_requests(): void
    {
        $agent = $this->staff('agent_accueil');

        $this->actingAs($agent)
            ->get(route('admin.driver-only-bookings.index'))
            ->assertForbidden();
    }

    public function test_solo_request_occupies_driver_availability(): void
    {
        $this->travelTo('2026-10-07 10:00:00');
        $driver = $this->driver();
        $startsAt = today()->addDays(2);
        $request = DriverOnlyBooking::factory()->create([
            'starts_at' => $startsAt->copy()->setTime(8, 0),
            'ends_at' => $startsAt->copy()->addDay()->setTime(19, 0),
            'days' => 1,
            'driver_id' => $driver->id,
        ]);

        $period = $this->period(1, 2);

        $this->assertSame(0, $this->availability()->freeDriverSlots($period));
        $this->assertDatabaseHas('driver_only_bookings', ['id' => $request->id]);
    }

    public function test_admin_escapes_customer_name_in_solo_driver_request(): void
    {
        $manager = $this->staff('gestionnaire');
        DriverOnlyBooking::factory()->create([
            'customer_name' => '<script>alert("xss")</script>',
        ]);

        $content = $this->actingAs($manager)
            ->get(route('admin.driver-only-bookings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $content);
    }

    public function test_solo_driver_form_rejects_missing_contact_or_invalid_dates(): void
    {
        $this->post('/fr/chauffeur-seul', [
            'name' => '',
            'phone' => '',
            'du' => 'invalid-date',
            'au' => '2026-01-01',
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertSessionHasErrors(['name', 'phone', 'du', 'au']);

        $this->assertDatabaseCount('driver_only_bookings', 0);
    }
}
