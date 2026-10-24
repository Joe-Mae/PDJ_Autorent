<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DocumentVerificationStatus;
use App\Models\Booking;
use App\Models\CustomerDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestBookingTest extends TestCase
{
    public function test_la_liste_et_la_fiche_du_vehicule_proposent_la_reservation_sans_compte(): void
    {
        $vehicle = $this->vehicle();

        $this->get(route('vehicles.index', ['locale' => 'fr']))
            ->assertSee(__('general.book'))
            ->assertSee('reserver-sans-compte');

        $this->get(route('vehicles.show', ['locale' => 'fr', 'vehicle' => $vehicle, 'du' => now()->addDay()->toDateString(), 'au' => now()->addDays(4)->toDateString()]))
            ->assertSee(__('general.book'))
            ->assertSee('reserver-sans-compte');
    }

    public function test_visiteur_peut_demander_une_reservation_sans_compte_et_voir_le_devis(): void
    {
        Storage::fake('documents');

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
        $segments = explode('/', trim(parse_url($location, PHP_URL_PATH), '/'));
        $token = $segments[count($segments) - 2];

        $response->assertRedirect(route('booking.guest.documents', [
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

        // Juste après l'envoi, le client est invité à déposer ses pièces.
        $this->get($location)
            ->assertOk()
            ->assertSee(__('booking.documents_title'))
            ->assertSee(__('document.type.piece_identite'))
            ->assertDontSee(__('document.type.permis'))
            ->assertSee('capture="environment"', false)
            ->assertSee('class="btn btn-outline-primary" disabled', false)
            ->assertSee(__('booking.guest.documents_required'));

        $documentsUrl = route('booking.guest.documents', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]);
        $this->get(route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]))->assertRedirect($documentsUrl);

        $storeUrl = route('booking.guest.documents.store', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]);
        $this->post($storeUrl, [
            'type' => 'piece_identite',
            'file' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect();

        $location = route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]);

        $this->get($location)
            ->assertSee(__('booking.guest.confirmation_title'))
            ->assertSee($booking->reference)
            ->assertSee($vehicle->fullName())
            ->assertSee('72 000', false)
            ->assertSee(__('booking.guest.confirmation_pending'))
            ->assertSee(__('booking.guest.documents_cta'));

        $this->actingAs($this->staff())
            ->post(route('admin.bookings.confirm', $booking))
            ->assertRedirect();

        $this->get($location)
            ->assertSee(__('booking.guest.confirmation_approved'));

        $this->assertSame(
            DocumentVerificationStatus::EnAttente,
            $booking->refresh()->document_verification_status,
        );
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
                'price_rental' => 36000,
                'price_driver' => 0,
                'price_total' => 36000,
            ]);
    }

    public function test_la_recherche_avec_chauffeur_conserve_l_option_et_le_total_jusqu_a_la_confirmation(): void
    {
        Storage::fake('documents');

        $this->driver();
        $vehicle = $this->vehicle();
        $start = now()->addDays(2);
        $dates = [
            'du' => $start->toDateString(),
            'au' => $start->copy()->addDays(4)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ];
        $bookingUrl = route('booking.guest.create', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
            ...$dates,
            'avec_chauffeur' => 1,
        ]);

        $this->get(route('vehicles.index', ['locale' => 'fr']).'?'.http_build_query($dates + [
            'avec_chauffeur' => 1,
        ]))
            ->assertSee('id="avec_chauffeur" checked', false)
            ->assertSee('100 000 FCFA', false)
            ->assertSee('28 000 FCFA', false)
            ->assertSee($bookingUrl);

        $this->get($bookingUrl)
            ->assertSee(__('booking.guest.driver_request_title'))
            ->assertSee('name="avec_chauffeur" value="1"', false)
            ->assertSee('72 000', false)
            ->assertSee('28 000', false)
            ->assertSee('100 000', false);

        $this->get(route('booking.guest.quote', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]).'?'.http_build_query($dates + ['avec_chauffeur' => 1]))
            ->assertExactJson([
                'days' => 4,
                'daily_price' => 18000,
                'price_rental' => 72000,
                'price_driver' => 28000,
                'price_total' => 100000,
            ]);

        $response = $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $dates + [
            'avec_chauffeur' => '1',
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90 00 00 00',
            'customer_email' => 'afi@example.com',
        ]);

        $booking = Booking::query()->firstOrFail();
        $location = $response->headers->get('Location');
        $segments = explode('/', trim(parse_url($location, PHP_URL_PATH), '/'));
        $token = $segments[count($segments) - 2];

        $response->assertRedirect(route('booking.guest.documents', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]));
        $this->assertTrue($booking->with_driver);
        $this->assertSame(72000, $booking->price_rental);
        $this->assertSame(28000, $booking->price_driver);
        $this->assertSame(100000, $booking->price_total);

        $this->post(route('booking.guest.documents.store', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]), [
            'type' => 'piece_identite',
            'file' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect();

        $this->get(route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]))
            ->assertSee(__('quote.line.driver', ['days' => 4]))
            ->assertSee('28 000 FCFA', false)
            ->assertSee('100 000 FCFA', false);
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
        $this->get(route('booking.guest.documents', [
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
        $this->post(route('booking.guest.documents.store', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => str_repeat('x', 64),
        ]), ['type' => 'piece_identite'])->assertNotFound();
    }

    public function test_le_client_invite_depose_ses_pieces_apres_sa_demande(): void
    {
        Storage::fake('documents');

        $vehicle = $this->vehicle();
        $response = $this->post(route('booking.guest.store', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
        ]), $this->guestData());

        $booking = Booking::query()->firstOrFail();
        $segments = explode('/', trim(parse_url($response->headers->get('Location'), PHP_URL_PATH), '/'));
        $token = $segments[count($segments) - 2];

        $storeUrl = route('booking.guest.documents.store', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]);

        $this->post($storeUrl, [
            'type' => 'piece_identite',
            'file' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->post($storeUrl, [
            'type' => 'piece_identite',
            'file' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect();

        $identity = CustomerDocument::query()->firstOrFail();
        $this->assertSame($booking->id, $identity->booking_id);
        $this->assertNull($identity->user_id);
        $this->assertSame('piece_identite', $identity->type->value);
        $this->assertSame('en_attente', $identity->status->value);
        Storage::disk('documents')->assertExists($identity->path);

        $this->post($storeUrl, [
            'type' => 'permis',
            'file' => UploadedFile::fake()->create('permis.pdf', 200, 'application/pdf'),
        ])->assertSessionHasErrors('type');

        $this->assertSame(1, $booking->documents()->count());

        $this->delete(route('booking.guest.documents.destroy', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
            'document' => $identity,
        ]))->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $identity->id]);
        $this->get(route('booking.guest.confirmation', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]))->assertRedirect(route('booking.guest.documents', [
            'locale' => 'fr',
            'booking' => $booking,
            'token' => $token,
        ]));
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
