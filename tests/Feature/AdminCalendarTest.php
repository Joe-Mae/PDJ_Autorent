<?php

namespace Tests\Feature;

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\BookingBlock;
use Tests\TestCase;

class AdminCalendarTest extends TestCase
{
    public function test_le_planning_affiche_les_vehicules_en_maintenance_et_les_periodes_multijours_actives(): void
    {
        $this->travelTo('2026-04-15 12:00:00');
        $vehicle = $this->vehicle(null, ['status' => VehicleStatus::EnMaintenance]);
        BookingBlock::query()->create([
            'vehicle_id' => $vehicle->id,
            'starts_at' => now()->startOfMonth()->addDays(9),
            'ends_at' => now()->startOfMonth()->addDays(12),
            'reason' => BlockReason::Maintenance,
        ]);
        Booking::factory()->on($vehicle)->create([
            'reference' => 'PDJ-CAL001',
            'status' => BookingStatus::Confirmee,
            'starts_at' => now()->startOfMonth()->addDays(10),
            'ends_at' => now()->startOfMonth()->addDays(13),
            'customer_name' => 'Client planning',
        ]);
        Booking::factory()->on($vehicle)->create([
            'reference' => 'PDJ-CAL002',
            'status' => BookingStatus::Annulee,
            'starts_at' => now()->startOfMonth()->addDays(10),
            'ends_at' => now()->startOfMonth()->addDays(13),
            'customer_name' => 'Réservation annulée',
        ]);

        $this->actingAs($this->staff('gestionnaire'))
            ->get('/admin/calendrier?mois=2026-04')
            ->assertOk()
            ->assertSee($vehicle->plate)
            ->assertSee('Client planning')
            ->assertDontSee('Réservation annulée');
    }
}
