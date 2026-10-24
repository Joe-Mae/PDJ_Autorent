<?php

namespace Tests\Feature;

use App\Models\Booking;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    public function test_les_reservations_invitees_sont_regroupees_par_client_et_montrent_leur_historique(): void
    {
        $vehicle = $this->vehicle();
        $first = Booking::factory()->on($vehicle)->create([
            'reference' => 'PDJ-GUEST1',
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '+228 90-00-00-01',
            'customer_email' => 'afi@example.com',
        ]);
        Booking::factory()->on($vehicle)->create([
            'reference' => 'PDJ-GUEST2',
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '22890000001',
            'customer_email' => 'afi@example.com',
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/clients')
            ->assertOk()
            ->assertSee('Clients sans compte')
            ->assertSee('Afi Mensah')
            ->assertSee('2');

        $this->get('/admin/clients/invites/'.$first->id)
            ->assertOk()
            ->assertSee('PDJ-GUEST1')
            ->assertSee('PDJ-GUEST2')
            ->assertSee('afi@example.com');
    }

    public function test_une_reservation_de_compte_n_est_pas_affichee_comme_client_invite(): void
    {
        $user = $this->customer(['first_name' => 'Kossi', 'last_name' => 'Doe']);
        $booking = Booking::factory()->for($user)->on($this->vehicle())->create([
            'customer_name' => null,
            'customer_phone' => null,
            'customer_email' => null,
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/clients/invites/'.$booking->id)
            ->assertNotFound();
    }
}
