<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentKind;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use Tests\TestCase;

/**
 * La caution est une décision de l'agence, véhicule par véhicule.
 *
 * Le montant reste porté par la catégorie. Ce qui change ici, c'est qu'une
 * voiture peut être louée sans caution : la réservation naît alors avec un
 * dépôt à zéro, l'étape de retrait est sautée et le retour se clôture sans
 * restitution. Les réservations déjà prises, elles, ne bougent pas.
 */
class OptionalDepositTest extends TestCase
{
    public function test_la_caution_est_exigee_par_defaut(): void
    {
        $booking = $this->confirmedBooking();

        $this->assertTrue($booking->requiresDeposit());
        $this->assertSame(self::DEPOSIT, (int) $booking->deposit_amount);
        $this->assertSame(72000 + self::DEPOSIT, $booking->amountToCarry());
    }

    public function test_un_vehicule_sans_caution_produit_une_reservation_sans_caution(): void
    {
        $booking = $this->confirmedBooking(depositRequired: false);

        $this->assertFalse($booking->requiresDeposit());
        $this->assertSame(0, (int) $booking->deposit_amount);
        $this->assertSame(72000, $booking->amountToCarry());
        $this->assertSame(72000, (int) $booking->price_total);
    }

    /** Le drapeau du véhicule ne réécrit pas les réservations déjà prises. */
    public function test_le_montant_reste_fige_si_l_agence_change_d_avis(): void
    {
        $vehicle = $this->vehicle();
        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(),
            'user' => null,
            'with_driver' => false,
        ]);

        $vehicle->update(['deposit_required' => false]);

        $this->assertTrue($booking->refresh()->requiresDeposit());
        $this->assertSame(self::DEPOSIT, (int) $booking->deposit_amount);
    }

    public function test_l_encaissement_saute_la_caution_quand_elle_n_est_pas_exigee(): void
    {
        $booking = $this->confirmedBooking(depositRequired: false);
        $agent = $this->staff();

        $this->actingAs($agent)
            ->post(route('agent.pickup.payment.store', $booking), [
                'method' => 'especes',
                'amount' => $booking->price_total,
            ])
            ->assertRedirect(route('agent.pickup.contract', $booking));

        $this->actingAs($agent)
            ->get(route('agent.pickup.deposit', $booking))
            ->assertRedirect(route('agent.pickup.contract', $booking));
    }

    public function test_l_encaissement_mene_toujours_a_la_caution_quand_elle_est_exigee(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->post(route('agent.pickup.payment.store', $booking), [
                'method' => 'especes',
                'amount' => $booking->price_total,
            ])
            ->assertRedirect(route('agent.pickup.deposit', $booking));
    }

    public function test_le_retour_se_cloture_sans_caution(): void
    {
        $booking = $this->bookingOnRoad(depositRequired: false);
        $agent = $this->staff();

        $this->actingAs($agent)
            ->get(route('agent.return.deposit', $booking))
            ->assertOk()
            ->assertSee(__('agent.finish_return'));

        $this->actingAs($agent)
            ->post(route('agent.return.deposit.store', $booking), [
                'deducted_amount' => 0,
            ])
            ->assertRedirect(route('agent.return.index'));

        $this->assertSame(BookingStatus::Terminee, $booking->refresh()->status);
        $this->assertSame(0, Payment::query()
            ->where('booking_id', $booking->id)
            ->where('kind', PaymentKind::DepositRefund->value)
            ->count());
    }

    public function test_la_fiche_masque_la_caution_quand_elle_n_est_pas_exigee(): void
    {
        $vehicle = $this->vehicle(null, ['deposit_required' => false]);

        $this->get($this->vehicleUrl($vehicle))
            ->assertOk()
            ->assertSee(__('general.pay_at_agency'))
            ->assertDontSee(__('general.deposit_to_prepare'));
    }

    public function test_la_fiche_affiche_la_caution_quand_elle_est_exigee(): void
    {
        $vehicle = $this->vehicle();

        $this->get($this->vehicleUrl($vehicle))
            ->assertOk()
            ->assertSee(__('general.deposit_to_prepare'));
    }

    private function vehicleUrl(Vehicle $vehicle): string
    {
        return route('vehicles.show', [
            'locale' => 'fr',
            'vehicle' => $vehicle,
            'du' => now()->addDay()->toDateString(),
            'au' => now()->addDays(4)->toDateString(),
            'heure_depart' => 8,
            'heure_retour' => 19,
        ]);
    }

    private function confirmedBooking(bool $depositRequired = true): Booking
    {
        return $this->bookings()->confirm(
            $this->bookings()->create([
                'vehicle' => $this->vehicle(null, ['deposit_required' => $depositRequired]),
                'period' => $this->period(),
                'user' => null,
                'with_driver' => false,
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]),
            $this->staff(),
        );
    }

    private function bookingOnRoad(bool $depositRequired = true): Booking
    {
        $booking = $this->confirmedBooking($depositRequired);
        $booking->update([
            'status' => BookingStatus::EnCours,
            'mileage_at_pickup' => 12000,
            'fuel_level_pickup' => 'full',
        ]);

        return $booking;
    }
}
