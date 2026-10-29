<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DriverOnlyBooking;
use Tests\TestCase;

/**
 * Fil des nouvelles demandes interrogé par la cloche de l'administration.
 *
 * La page n'a pas de websocket : elle interroge ce point JSON toutes les
 * quinze secondes. Le test vérifie le contrat minimal dont dépend le
 * JavaScript (compteur, dernière demande, lien) et la présence du pop-up
 * sonore dans le gabarit du back-office.
 */
class AdminNotificationFeedTest extends TestCase
{
    public function test_feed_is_reserved_for_staff(): void
    {
        $this->getJson(route('admin.notifications.feed'))->assertUnauthorized();
    }

    public function test_feed_returns_pending_count_and_the_latest_location_request(): void
    {
        $booking = Booking::factory()->create([
            'reference' => 'PDJ-FEED01',
            'customer_name' => 'Afi Mensah',
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->staff())->getJson(route('admin.notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('pending', 1)
            ->assertJsonPath('latest.kind', 'booking')
            ->assertJsonPath('latest.reference', 'PDJ-FEED01')
            ->assertJsonPath('latest.customer', 'Afi Mensah')
            ->assertJsonPath('latest.url', route('admin.bookings.show', ['booking' => $booking]));
    }

    public function test_feed_ignores_handled_requests_and_keeps_the_newest_one(): void
    {
        Booking::factory()->confirmed()->create(['created_at' => now()->subMinutes(30)]);
        Booking::factory()->create([
            'reference' => 'PDJ-OLD001',
            'customer_name' => 'Ancienne demande',
            'created_at' => now()->subMinutes(20),
        ]);
        DriverOnlyBooking::factory()->create([
            'reference' => 'PDJ-SOLO01',
            'customer_name' => 'Kossi Adjo',
            'created_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($this->staff())->getJson(route('admin.notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('pending', 2)
            ->assertJsonPath('latest.kind', 'driver_only')
            ->assertJsonPath('latest.reference', 'PDJ-SOLO01')
            ->assertJsonPath('latest.customer', 'Kossi Adjo')
            ->assertJsonPath('latest.vehicle', __('driver.solo_title'));
    }

    public function test_feed_has_no_latest_request_when_nothing_is_pending(): void
    {
        Booking::factory()->confirmed()->create();

        $response = $this->actingAs($this->staff())->getJson(route('admin.notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('pending', 0)
            ->assertJsonPath('latest', null);
    }

    public function test_admin_layout_embeds_the_sonorous_request_popup(): void
    {
        $response = $this->actingAs($this->staff('gestionnaire'))->get('/admin');

        $response->assertOk()
            ->assertSee('data-admin-notification-bell', false)
            ->assertSee('data-admin-notification-feed="'.route('admin.notifications.feed').'"', false)
            ->assertSee('data-poll-interval="15000"', false)
            ->assertSee('id="adminRequestToast"', false)
            ->assertSee('Nouvelle demande de réservation');
    }
}
