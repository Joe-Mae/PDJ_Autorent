<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\SettingsService;
use Tests\TestCase;

/**
 * Les écrans du guichet doivent s'afficher, pas seulement valider.
 *
 * L'encaissement appelait `receiptLines()` sur le créneau au lieu du devis :
 * la page tombait en 500 au moment où l'agent devait encaisser. Ce test fait
 * le tour des écrans du retrait et du retour pour attraper ce genre de faute.
 */
class AgentScreensTest extends TestCase
{
    public function test_les_ecrans_du_retrait_s_affichent(): void
    {
        $booking = $this->confirmedBooking();
        $agent = $this->staff();

        foreach ([
            route('agent.pickup.documents', $booking),
            route('agent.pickup.condition', $booking),
            route('agent.pickup.payment', $booking),
            route('agent.pickup.deposit', $booking),
            route('agent.pickup.contract', $booking),
            route('agent.pickup.keys', $booking),
        ] as $url) {
            $this->actingAs($agent)->get($url)->assertOk();
        }
    }

    public function test_l_encaissement_detaille_la_location_et_le_chauffeur(): void
    {
        $this->driver();
        $this->driver();
        $booking = $this->confirmedBooking(withDriver: true);
        $money = app(SettingsService::class);

        $this->actingAs($this->staff())
            ->get(route('agent.pickup.payment', $booking))
            ->assertOk()
            ->assertSee(__('quote.line.rental', ['days' => $booking->days]))
            ->assertSee(__('quote.line.driver', ['days' => $booking->days]))
            ->assertSee($money->money($booking->price_total));
    }

    public function test_les_ecrans_du_retour_s_affichent(): void
    {
        $booking = $this->bookingOnRoad();
        $agent = $this->staff();

        foreach ([
            route('agent.return.index'),
            route('agent.return.control', $booking),
            route('agent.return.deposit', $booking),
        ] as $url) {
            $this->actingAs($agent)->get($url)->assertOk();
        }
    }

    private function confirmedBooking(bool $withDriver = false): Booking
    {
        return $this->bookings()->confirm(
            $this->bookings()->create([
                'vehicle' => $this->vehicle(),
                'period' => $this->period(),
                'user' => null,
                'with_driver' => $withDriver,
                'customer_name' => 'Afi Mensah',
                'customer_phone' => '+228 90 00 00 00',
            ]),
            $this->staff(),
        );
    }

    private function bookingOnRoad(): Booking
    {
        $booking = $this->confirmedBooking();
        $booking->update([
            'status' => BookingStatus::EnCours,
            'mileage_at_pickup' => 12000,
            'fuel_level_pickup' => 'full',
        ]);

        return $booking;
    }
}
