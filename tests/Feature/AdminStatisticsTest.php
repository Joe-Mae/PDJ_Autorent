<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Payment;
use Tests\TestCase;

class AdminStatisticsTest extends TestCase
{
    public function test_les_statistiques_comptent_les_locations_et_les_encaissements_reels(): void
    {
        $this->travelTo('2026-04-15 12:00:00');
        $vehicle = $this->vehicle();
        $completed = Booking::factory()->on($vehicle)->create([
            'status' => BookingStatus::Terminee,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(3),
            'started_at' => now()->subDays(5),
            'ended_at' => now()->subDays(3),
            'price_total' => 99000,
        ]);
        Booking::factory()->on($vehicle)->create([
            'status' => BookingStatus::Annulee,
            'starts_at' => now()->subDays(4),
            'ends_at' => now()->subDays(2),
        ]);
        Payment::query()->create([
            'booking_id' => $completed->id,
            'kind' => PaymentKind::Rental,
            'method' => PaymentMethod::Especes,
            'amount' => 45000,
            'recorded_at' => now()->subDay(),
        ]);

        $this->actingAs($this->staff('gestionnaire'))
            ->get('/admin/statistiques?periode=mois')
            ->assertOk()
            ->assertSee('Réservations par période')
            ->assertSee('Locations terminées')
            ->assertSee('Revenus réellement encaissés')
            ->assertSee('45 000')
            ->assertDontSee('99 000');
    }

    public function test_un_agent_ne_peut_pas_ouvrir_les_statistiques_de_direction(): void
    {
        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin/statistiques')
            ->assertForbidden();
    }
}
