<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DriverOnlyBooking;
use Tests\TestCase;

/**
 * La page « Consulter ma réservation » accepte la référence ou le nom du
 * client. Un nom peut correspondre à plusieurs dossiers : on liste alors les
 * références au lieu d'afficher le détail de tous les homonymes.
 */
class BookingLookupTest extends TestCase
{
    public function test_la_recherche_par_reference_affiche_la_fiche_complete(): void
    {
        $booking = Booking::factory()->create(['customer_name' => 'Afi Mensah']);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => strtolower($booking->reference),
        ])
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Afi Mensah')
            ->assertSee(__('booking.lookup.document_verification'));
    }

    public function test_la_recherche_par_nom_trouve_une_reservation_invitee(): void
    {
        $booking = Booking::factory()->create(['customer_name' => 'Afi Mensah']);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'mensah',
        ])
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Afi Mensah')
            ->assertSee(__('booking.lookup.document_verification'));
    }

    public function test_les_mots_du_nom_peuvent_etre_saisis_dans_n_importe_quel_ordre(): void
    {
        $booking = Booking::factory()->create(['customer_name' => 'Afi Mensah']);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'Mensah Afi',
        ])
            ->assertOk()
            ->assertSee($booking->reference);
    }

    public function test_la_recherche_par_nom_couvre_le_compte_du_client(): void
    {
        $customer = $this->customer([
            'first_name' => 'Kodjo',
            'last_name' => 'Amegan',
            'name' => 'Kodjo Amegan',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'customer_name' => null,
        ]);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'amegan',
        ])
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Kodjo Amegan');
    }

    public function test_plusieurs_correspondances_sont_listees_sans_leurs_details(): void
    {
        $first = Booking::factory()->create(['customer_name' => 'Afi Mensah']);
        $second = Booking::factory()->create(['customer_name' => 'Afi Mensah']);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'afi',
        ])
            ->assertOk()
            ->assertSee(__('booking.lookup.results_title'))
            ->assertSee($first->reference)
            ->assertSee($second->reference)
            ->assertDontSee(__('booking.lookup.document_verification'));
    }

    public function test_la_recherche_par_nom_trouve_une_demande_chauffeur_seul(): void
    {
        $booking = DriverOnlyBooking::factory()->create(['customer_name' => 'Afi Mensah']);

        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'afi mensah',
        ])
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee(__('driver.solo_title'));
    }

    public function test_une_recherche_sans_correspondance_affiche_un_message(): void
    {
        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'Personne Inconnue',
        ])
            ->assertOk()
            ->assertSee(__('booking.lookup.not_found'));
    }

    public function test_une_recherche_trop_courte_est_refusee(): void
    {
        $this->post(route('booking.lookup.store', ['locale' => 'fr']), [
            'query' => 'ab',
        ])->assertSessionHasErrors('query');
    }
}
