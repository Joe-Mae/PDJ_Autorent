<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Tests\TestCase;

class AdminBookingListTest extends TestCase
{
    public function test_la_recherche_par_email_et_le_filtre_vehicule_sont_appliques(): void
    {
        $vehicle = $this->vehicle();
        $otherVehicle = $this->vehicle();
        $matching = Booking::factory()->on($vehicle)->create([
            'reference' => 'PDJ-EMAIL1',
            'customer_name' => 'Client invité',
            'customer_email' => 'client@example.com',
        ]);
        $other = Booking::factory()->on($otherVehicle)->create([
            'reference' => 'PDJ-OTHER1',
            'customer_email' => 'autre@example.com',
        ]);

        $response = $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/reservations?q=client%40example.com&vehicule='.$vehicle->id);

        $response->assertOk()
            ->assertSee($matching->reference)
            ->assertDontSee($other->reference)
            ->assertSee('client@example.com');
    }

    public function test_la_liste_montre_le_statut_de_la_reservation_sans_la_verification_des_documents(): void
    {
        $booking = Booking::factory()->confirmed()->create(['reference' => 'PDJ-STATUT']);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/reservations')
            ->assertOk()
            ->assertSee($booking->status->label())
            ->assertDontSee('admin.document_verification');
    }

    public function test_chaque_statut_de_la_liste_porte_sa_propre_couleur(): void
    {
        Booking::factory()->create([
            'reference' => 'PDJ-REFUSE',
            'status' => BookingStatus::Refusee,
        ]);
        Booking::factory()->started()->create(['reference' => 'PDJ-SORTI']);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/reservations')
            ->assertOk()
            ->assertSee('bg-refusee', false)
            ->assertSee('bg-encours', false)
            ->assertDontSee('bg-success', false);
    }
}
