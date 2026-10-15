<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Tests\TestCase;

class GuestBookingTest extends TestCase
{
    public function test_la_liste_et_la_fiche_du_vehicule_proposent_la_reservation_sans_compte(): void
    {
        $vehicle = $this->vehicle();

        $this->get(route('vehicles.index', ['locale' => 'fr']))
            ->assertSee(__('general.book'))
            ->assertSee('reserver-sans-compte');

        $this->get(route('vehicles.show', ['locale' => 'fr', 'vehicle' => $vehicle]))
            ->assertSee(__('general.book'))
            ->assertSee('reserver-sans-compte');
    }

    public function test_visiteur_peut_demander_une_reservation_sans_compte_et_voir_le_devis(): void
    {
        $vehicle = $this->vehicle();
        $data = $this->guestData();

        $this->get(route('booking.guest.create', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
            'du' => $data['du'],
            'au' => $data['au'],
            'heure_depart' => $data['heure_depart'],
            'heure_retour' => $data['heure_retour'],
        ]))
            ->assertSee(__('booking.guest.daily_rate'))
            ->assertSee('72 000', false)
            ->assertSee(__('booking.guest.estimated_total'));

        $response = $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $data + ['price_total' => 1]);

        $booking = Booking::query()->firstOrFail();
        $location = $response->headers->get('Location');
        $token = basename(parse_url($location, PHP_URL_PATH));

        $response->assertRedirect(route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]));
        $this->assertSame(64, strlen($token));
        $this->assertSame(BookingStatus::EnAttente, $booking->status);
        $this->assertNull($booking->user_id);
        $this->assertSame(72000, $booking->price_total);
        $this->assertSame(hash('sha256', $token), $booking->guest_access_token_hash);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
            'customer_comment' => 'Arrivée après 18 h.',
        ]);

        $this->get($location)
            ->assertSee(__('booking.guest.confirmation_title'))
            ->assertSee($booking->reference)
            ->assertSee($vehicle->fullName())
            ->assertSee('72 000', false)
            ->assertSee(__('booking.guest.confirmation_pending'));

        $this->actingAs($this->staff())
            ->post(route('admin.bookings.confirm', $booking))
            ->assertRedirect();

        $this->get($location)
            ->assertSee(__('booking.guest.confirmation_approved'));
    }

    public function test_le_devis_se_recalcule_pour_les_dates_demandees(): void
    {
        $vehicle = $this->vehicle();
        $start = now()->addDay();

        $this->get(route('booking.guest.quote', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
            'du' => $start->toDateString(),
            'au' => $start->copy()->addDays(2)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]))
            ->assertExactJson([
                'days' => 2,
                'daily_price' => 18000,
                'price_total' => 36000,
            ]);
    }

    public function test_une_reference_seule_ne_permet_pas_d_acceder_a_la_confirmation_guest(): void
    {
        $vehicle = $this->vehicle();
        $data = $this->guestData();

        $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $data);

        $booking = Booking::query()->firstOrFail();

        $this->get(route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => str_repeat('x', 64),
        ]))->assertNotFound();

        $this->get(route('booking.show', ['locale' => 'fr', 'booking' => $booking]))
            ->assertNotFound();
        $this->get(route('booking.receipt', ['locale' => 'fr', 'booking' => $booking]))
            ->assertNotFound();
        $this->post(route('booking.documents.store', ['locale' => 'fr', 'booking' => $booking]))
            ->assertNotFound();
    }

    public function test_le_lien_de_rappel_guest_confirme_la_venue_sans_exposer_la_reservation(): void
    {
        $vehicle = $this->vehicle();
        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => false,
            'phone_verified_at' => null,
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
            'guest_access_token_hash' => hash('sha256', str_repeat('a', 64)),
        ]);

        $this->get('/fr/confirmer-ma-venue/'.$booking->public_token)
            ->assertRedirect(route('home', ['locale' => 'fr']))
            ->assertSessionHas('status', __('booking.presence_confirmed'));

        $this->assertNotNull($booking->refresh()->client_confirmed_presence_at);
    }

    public function test_la_demande_guest_refuse_les_coordonnees_manquantes(): void
    {
        $vehicle = $this->vehicle();
        $data = $this->guestData();
        $data['customer_phone'] = '';

        $this->from(route('booking.guest.create', ['locale' => 'fr', 'vehicle' => $vehicle]))
            ->post(route('booking.guest.store', ['locale' => 'fr', 'vehicle' => $vehicle]), $data)
            ->assertSessionHasErrors('customer_phone');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_la_demande_guest_refuse_un_creneau_deja_reserve(): void
    {
        $vehicle = $this->vehicle();
        $data = $this->guestData();

        $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $data)->assertRedirect();

        $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $data)->assertSessionHas('error');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_la_demande_guest_refuse_une_heure_de_retour_avant_le_depart(): void
    {
        $vehicle = $this->vehicle();
        $data = $this->guestData();
        $data['au'] = $data['du'];
        $data['heure_depart'] = 19;
        $data['heure_retour'] = 8;

        $this->from(route('booking.guest.create', ['locale' => 'fr', 'vehicle' => $vehicle]))
            ->post(route('booking.guest.store', ['locale' => 'fr', 'vehicle' => $vehicle]), $data)
            ->assertSessionHasErrors('heure_retour');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_l_administration_peut_rechercher_confirmer_et_consulter_une_demande_guest(): void
    {
        $vehicle = $this->vehicle();
        $agent = $this->staff();
        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => false,
            'phone_verified_at' => null,
            'customer_name' => 'Client <script>',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
            'customer_comment' => '<script>alert(1)</script>',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.bookings.index', ['q' => '90 00 00 00']))
            ->assertSee('Client &lt;script&gt;', false);

        $this->get(route('admin.bookings.show', $booking))
            ->assertSee('afi@example.com')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->post(route('admin.bookings.confirm', $booking))
            ->assertRedirect();

        $this->assertSame(BookingStatus::Confirmee, $booking->refresh()->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function guestData(): array
    {
        $start = now()->addDay();

        return [
            'du' => $start->toDateString(),
            'au' => $start->copy()->addDays(4)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
            'customer_comment' => 'Arrivée après 18 h.',
        ];
    }
}
