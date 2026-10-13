<?php

namespace Tests\Feature;

use App\Enums\DriverOnlyBookingStatus;
use App\Models\DriverOnlyBooking;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DriverOnlyBookingTest extends TestCase
{
    public static function soloDriverRates(): array
    {
        return [
            'one day' => [1, 8000],
            'three days' => [3, 8000],
            'four days' => [4, 6500],
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
        ]);

        $booking = DriverOnlyBooking::query()->firstOrFail();
        $response->assertRedirect(route('page.driver', ['locale' => 'fr']));
        $response->assertSessionHas('driver_only_booking_reference', $booking->reference);
        $this->assertSame('Afi Mensah', $booking->customer_name);
        $this->assertSame('+228 90 12 34 56', $booking->phone);
        $this->assertSame($days, $booking->days);
        $this->assertSame($dailyRate, $booking->daily_price);
        $this->assertSame($days * $dailyRate, $booking->price_total);
        $this->assertSame(DriverOnlyBookingStatus::EnAttente, $booking->status);
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
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('driver_only_bookings', 0);
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
        ])->assertSessionHasErrors(['name', 'phone', 'du', 'au']);

        $this->assertDatabaseCount('driver_only_bookings', 0);
    }
}
