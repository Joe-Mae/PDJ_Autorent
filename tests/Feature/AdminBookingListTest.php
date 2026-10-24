<?php

namespace Tests\Feature;

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
}
