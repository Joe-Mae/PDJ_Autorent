<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_dashboard_affiche_les_indicateurs_reels_et_les_retours_en_retard(): void
    {
        $this->travelTo('2026-04-15 12:00:00');

        $available = $this->vehicle();
        $reserved = $this->vehicle(null, ['status' => VehicleStatus::Reserve]);
        $rented = $this->vehicle(null, ['status' => VehicleStatus::EnLocation]);
        $this->vehicle(null, ['status' => VehicleStatus::EnMaintenance]);

        $overdue = Booking::factory()->on($rented)->started()->create([
            'reference' => 'PDJ-LATE01',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subHour(),
            'customer_name' => 'Afi Mensah',
            'customer_phone' => '90000001',
        ]);

        Booking::factory()->on($reserved)->create([
            'status' => BookingStatus::Confirmee,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(4),
        ]);

        Booking::factory()->on($available)->create([
            'status' => BookingStatus::Terminee,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->staff('agent_accueil'))->get('/admin');

        $response->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total_vehicles'] === 4
                && $stats['available'] === 2
                && $stats['en_location'] === 1
                && $stats['unavailable'] === 1
                && $stats['pending'] === 0
                && $stats['confirmed'] === 1
                && $stats['in_progress'] === 1
                && $stats['overdue'] === 1
                && $stats['completed'] === 1)
            ->assertSee('Véhicules disponibles')
            ->assertSee('Véhicules en location')
            ->assertSee('En maintenance / indisponibles')
            ->assertSee('Réservations en attente')
            ->assertSee('Locations en retard')
            ->assertSee('PDJ-LATE01')
            ->assertSee('Afi Mensah')
            ->assertSee('Retours prévus');
    }
}
