<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use Tests\TestCase;

/**
 * Absence au retrait : rappel aux agents à 2 h, annulation automatique à 5 h.
 *
 * Le délai est porté par grace_expires_at, donc les tests fixent directement
 * l'échéance plutôt que de simuler cinq heures de navigation.
 */
class NoShowWorkflowTest extends TestCase
{
    public function test_la_creation_fixe_l_echeance_d_absence_a_cinq_heures(): void
    {
        $period = $this->period(4);

        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);

        $this->assertTrue(
            $booking->grace_expires_at->equalTo($period->startsAt->addHours(5)),
            'La voiture doit être bloquée cinq heures après l’heure de retrait convenue.',
        );
    }

    public function test_la_reservation_s_annule_seule_a_l_echeance_et_libere_la_voiture(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $vehicle = $this->vehicle(null, ['status' => VehicleStatus::Reserve]);
        $booking = Booking::factory()->on($vehicle)->confirmed()->create([
            'starts_at' => now()->subHours(5)->subMinute(),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->subMinute(),
            'customer_name' => 'Afi Mensah',
        ]);

        $this->artisan('bookings:mark-no-shows')->assertSuccessful();

        $booking->refresh();
        $this->assertSame(BookingStatus::NonPresente, $booking->status);
        $this->assertSame(VehicleStatus::Disponible, $vehicle->refresh()->status);
        $this->assertSame('systeme', $booking->cancelled_by);
    }

    public function test_aucune_annulation_avant_l_echeance(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $booking = Booking::factory()->confirmed()->create([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(2),
        ]);

        $this->artisan('bookings:mark-no-shows')->assertSuccessful();

        $this->assertSame(BookingStatus::Confirmee, $booking->refresh()->status);
    }

    public function test_le_popup_d_absence_apparait_deux_heures_apres_l_heure_convenue(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        Booking::factory()->confirmed()->create([
            'reference' => 'PDJ-LATE01',
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(2),
            'customer_name' => 'Afi Mensah',
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.no_show_title'))
            ->assertSee('PDJ-LATE01');
    }

    public function test_le_popup_n_apparait_pas_avant_deux_heures_de_retard(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        Booking::factory()->confirmed()->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(4),
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(__('admin.no_show_title'));
    }

    public function test_un_agent_decale_l_heure_de_retrait_et_repousse_l_echeance(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $booking = Booking::factory()->confirmed()->create([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->post(route('admin.bookings.extend', $booking), ['heures' => 2])
            ->assertRedirect();

        $booking->refresh();
        $this->assertTrue($booking->pickup_extended_until->equalTo(now()->addHours(2)));
        $this->assertTrue(
            $booking->grace_expires_at->equalTo(now()->addHours(7)),
            'L’annulation automatique repart de la nouvelle heure convenue.',
        );

        // Le client n'est plus en retard : il sort du pop-up.
        $this->actingAs($this->staff('agent_accueil'))
            ->get('/admin')
            ->assertDontSee(__('admin.no_show_title'));
    }

    public function test_un_agent_annule_avec_un_motif(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $booking = Booking::factory()->confirmed()->create([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->post(route('admin.bookings.no-show.cancel', $booking), [
                'motif' => 'Client injoignable après trois appels',
            ])
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::Annulee, $booking->status);
        $this->assertSame('Client injoignable après trois appels', $booking->status_reason);
        $this->assertSame('agence', $booking->cancelled_by);
    }

    public function test_le_motif_est_obligatoire_pour_annuler_depuis_le_popup(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $booking = Booking::factory()->confirmed()->create([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
            'grace_expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->post(route('admin.bookings.no-show.cancel', $booking), [])
            ->assertSessionHasErrors('motif');

        $this->assertSame(BookingStatus::Confirmee, $booking->refresh()->status);
    }

    public function test_on_ne_decale_pas_l_heure_d_une_reservation_deja_annulee(): void
    {
        $this->travelTo('2026-11-02 14:00:00');

        $booking = Booking::factory()->cancelled()->create([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($this->staff('agent_accueil'))
            ->post(route('admin.bookings.extend', $booking), ['heures' => 2])
            ->assertSessionHas('error');

        $this->assertNull($booking->refresh()->pickup_extended_until);
    }
}
