<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Tests\TestCase;

/**
 * Le tunnel de réservation, par HTTP.
 *
 * C'est le seul chemin qui transforme un visiteur en réservation. Chaque étape
 * est testée pour ce qu'elle promet à l'écran : où ça redirige, ce qui est
 * écrit en base, et ce que le visiteur reçoit quand la demande est impossible.
 */
class BookingTunnelTest extends TestCase
{
    public function test_un_visiteur_cree_une_reservation_avec_un_montant_fige(): void
    {
        $vehicle = $this->vehicle();

        $response = $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('booking.show', ['locale' => 'fr', 'booking' => $booking]));
        $this->assertSame(4, $booking->days);
        $this->assertSame(72000, $booking->price_total);
        $this->assertSame(BookingStatus::EnAttente, $booking->status);
    }

    public function test_un_visiteur_peut_demander_le_chauffeur_des_la_creation(): void
    {
        $this->driver();
        $vehicle = $this->vehicle();

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
            'avec_chauffeur' => '1',
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->assertTrue($booking->with_driver);
        $this->assertSame(12, $booking->driver_blocks);
        $this->assertSame(7000, $booking->driver_daily_price);
        $this->assertSame(100000, $booking->price_total);
    }

    public function test_page_de_reservation_conserve_le_tarif_chauffeur_deja_enregistre(): void
    {
        $customer = $this->customer();
        $this->driver();
        $this->driver();
        $vehicle = $this->vehicle();

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => $customer,
            'with_driver' => true,
        ]);

        $this->settings()->put('driver.block_price', 9000);

        $this->actingAs($customer)
            ->get(route('booking.show', ['locale' => 'fr', 'booking' => $booking]))
            ->assertSee('28 000 FCFA', false)
            ->assertSee('100 000 FCFA', false);
    }

    public function test_le_recapitulatif_affiche_l_ancien_tarif_chauffeur_par_blocs(): void
    {
        $customer = $this->customer();
        $this->driver();
        $vehicle = $this->vehicle();
        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => $customer,
            'with_driver' => true,
        ]);
        $booking->update([
            'driver_daily_price' => null,
            'driver_blocks' => 12,
            'price_driver' => 60000,
            'price_total' => 132000,
        ]);

        $this->actingAs($customer)
            ->get(route('booking.show', ['locale' => 'fr', 'booking' => $booking]))
            ->assertSee(__('booking.line.driver', ['blocks' => 12]))
            ->assertSee('60 000 FCFA', false)
            ->assertDontSee(__('quote.line.driver', ['days' => 4]));
    }

    /**
     * Le tunnel ne doit pas créer une réservation fantôme : la liste des
     * véhicules de la page de recherche ne propose que des véhicules libres,
     * mais un client qui a mis l'onglet en favori revient des heures plus tard
     * et clique sur « Réserver » sans vérifier.
     */
    public function test_une_reservation_demandee_tard_apres_occupation_est_refusee(): void
    {
        $vehicle = $this->vehicle();
        $dates = [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ];

        $this->post('/fr/reservation', $dates)->assertRedirect();

        $this->assertSame(1, Booking::query()->count(), 'La première réservation doit exister.');

        $this->post('/fr/reservation', $dates)->assertSessionHas('error');

        $this->assertSame(1, Booking::query()->count(), 'La seconde demande ne doit pas créer de réservation.');
    }

    public function test_refuse_une_reservation_hors_des_heures_d_ouverture(): void
    {
        $vehicle = $this->vehicle();

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(3)->toDateString(),
            'heure_depart' => 6,
            'heure_retour' => 22,
        ])->assertSessionHasErrors('heure');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_refuse_une_date_de_retour_anterieure_a_la_date_de_depart(): void
    {
        $vehicle = $this->vehicle();

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDays(5)->toDateString(),
            'au' => now()->addDay()->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHasErrors('au');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_refuse_un_vehicule_inexistant(): void
    {
        $this->post('/fr/reservation', [
            'vehicle_id' => 9999,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(3)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHasErrors('vehicle_id');
    }

    public function test_refuse_un_vehicule_non_reservable(): void
    {
        $vehicle = $this->vehicle(null, ['is_active' => false]);

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(3)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHas('error');

        $this->assertSame(0, Booking::query()->count());
    }

    /**
     * Un client connecté qui reclique sur « Réserver » pour exactement la même
     * voiture et les mêmes dates ne doit pas se retrouver avec deux
     * réservations concurrentes sur le même véhicule.
     */
    public function test_un_client_qui_reclique_recupere_sa_reservation_existante(): void
    {
        $customer = $this->customer();
        $vehicle = $this->vehicle();

        $dates = [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ];

        $this->actingAs($customer)->post('/fr/reservation', $dates);
        $booking = Booking::query()->firstOrFail();

        $this->actingAs($customer)->post('/fr/reservation', $dates)
            ->assertRedirect(route('booking.show', ['locale' => 'fr', 'booking' => $booking]));

        $this->assertSame(1, Booking::query()->count());
    }

    /**
     * Règle anti-fantôme : au plus deux réservations actives par client. Sans
     * ce garde-fou, une même personne bloque tout le parc en moins d'une heure.
     */
    public function test_un_client_au_dela_du_maximum_de_reservations_actives_est_refuse(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);

        foreach ([1, 9] as $offset) {
            $this->post('/fr/reservation', [
                'vehicle_id' => $this->vehicle()->id,
                'du' => now()->addDays($offset)->toDateString(),
                'au' => now()->addDays($offset + 2)->toDateString(),
                'heure_depart' => 8,
                'heure_retour' => 19,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Booking::query()->count());

        $this->post('/fr/reservation', [
            'vehicle_id' => $this->vehicle()->id,
            'du' => now()->addDays(20)->toDateString(),
            'au' => now()->addDays(22)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHas('error');

        $this->assertSame(2, Booking::query()->count());
    }

    public function test_un_client_bloque_ne_peut_pas_reserver(): void
    {
        $customer = $this->customer(['blocked_at' => now(), 'no_show_count' => 2]);

        $this->actingAs($customer)->post('/fr/reservation', [
            'vehicle_id' => $this->vehicle()->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(3)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHas('error');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_un_client_mineur_ne_peut_pas_reserver(): void
    {
        $minor = $this->customer(['birth_date' => now()->subYears(17)->toDateString()]);

        $this->actingAs($minor)->post('/fr/reservation', [
            'vehicle_id' => $this->vehicle()->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(3)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ])->assertSessionHas('error');

        $this->assertSame(0, Booking::query()->count());
    }

    // ── Accès à une réservation ──────────────────────────────────────────────

    public function test_un_client_ne_peut_pas_ouvrir_la_reservation_d_un_autre(): void
    {
        $booking = $this->bookingFor($this->customer());

        $this->actingAs($this->customer())
            ->get('/fr/reservation/'.$booking->reference)
            ->assertForbidden();
    }

    /**
     * Régression de sécurité : une réservation créée sans compte est orpheline.
     * N'importe quel compte connecté ne doit pas pouvoir s'en attribuer la
     * propriété en visitant son adresse — la référence se lit et se dicte au
     * téléphone. Seul celui qui l'a créée dans ce navigateur peut la rattacher.
     */
    public function test_un_compte_quelconque_ne_peut_pas_prendre_une_reservation_orpheline(): void
    {
        $booking = $this->bookingFor(null);

        $this->actingAs($this->customer())
            ->get('/fr/reservation/'.$booking->reference)
            ->assertForbidden();

        $this->assertNull($booking->refresh()->user_id);
    }

    public function test_le_visiteur_qui_a_cree_la_reservation_peut_la_rattacher_a_son_compte(): void
    {
        $this->actingAs($this->customer());

        $vehicle = $this->vehicle();

        $this->post('/fr/reservation', [
            'vehicle_id' => $vehicle->id,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(5)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]);

        $booking = Booking::query()->firstOrFail();
        $this->assertNotNull($booking->user_id);
    }

    public function test_un_client_peut_voir_la_reservation_qui_lui_appartient(): void
    {
        $customer = $this->customer();
        $booking = $this->bookingFor($customer);

        $this->actingAs($customer)
            ->get('/fr/reservation/'.$booking->reference)
            ->assertOk();
    }

    // ── Bouton « Je confirme ma venue » du rappel ───────────────────────────

    /**
     * Le lien arrive dans un SMS ou un WhatsApp : il doit fonctionner sans
     * compte, sans session et dans la langue du message.
     */
    public function test_le_bouton_de_confirmation_de_venue_fonctionne_sans_compte(): void
    {
        $booking = $this->bookingFor($this->customer(['preferred_locale' => 'fr']));

        $this->get('/fr/confirmer-ma-venue/'.$booking->public_token)
            ->assertRedirect(route('booking.show', ['locale' => 'fr', 'booking' => $booking]));

        $this->assertNotNull($booking->refresh()->client_confirmed_presence_at);
    }

    public function test_le_bouton_de_confirmation_de_venue_renvoie_un_client_anglais_en_anglais(): void
    {
        $booking = $this->bookingFor($this->customer(['preferred_locale' => 'en']));

        // Le lien peut être cliqué depuis un message en anglais : le client doit
        // retomber sur sa réservation en anglais, pas en français.
        $this->get('/fr/confirmer-ma-venue/'.$booking->public_token)
            ->assertRedirect(route('booking.show', ['locale' => 'en', 'booking' => $booking]));
    }

    public function test_un_jeton_inconnu_ne_confirme_aucune_venue(): void
    {
        $booking = $this->bookingFor($this->customer());

        $this->get('/fr/confirmer-ma-venue/jeton-invente-au-hasard')->assertNotFound();

        $this->assertNull($booking->refresh()->client_confirmed_presence_at);
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    protected function bookingFor(?User $customer): Booking
    {
        return $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(4),
            'user' => $customer,
            'with_driver' => false,
        ]);
    }
}
