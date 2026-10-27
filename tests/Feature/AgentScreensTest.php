<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\SettingsService;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_la_page_retour_liste_les_vehicules_sortis_avec_leur_controle(): void
    {
        $booking = $this->bookingOnRoad();

        $this->actingAs($this->staff())
            ->get(route('agent.return.index'))
            ->assertOk()
            ->assertSee($booking->vehicle->displayName())
            ->assertSee($booking->vehicle->plate)
            ->assertSee(route('agent.return.control', $booking), false)
            ->assertSee(__('agent.return_control'))
            ->assertDontSee(__('agent.no_vehicle_out'));
    }

    public function test_la_page_retour_annonce_les_retours_a_venir(): void
    {
        $booking = $this->confirmedBooking();

        $this->actingAs($this->staff())
            ->get(route('agent.return.index'))
            ->assertOk()
            ->assertSee(__('agent.upcoming_returns'))
            ->assertSee(__('agent.not_out_yet'))
            ->assertSee($booking->vehicle->plate);
    }

    public function test_la_page_retour_vide_renvoie_vers_les_reservations(): void
    {
        $this->actingAs($this->staff())
            ->get(route('agent.return.index'))
            ->assertOk()
            ->assertSee(__('agent.no_vehicle_out'))
            ->assertSee(route('admin.bookings.index'), false);
    }

    /**
     * Le retour se pilote au guichet, mais chaque rôle doit pouvoir l'ouvrir
     * depuis son propre menu, sans repasser par le tableau de bord.
     */
    #[DataProvider('roles')]
    public function test_le_menu_admin_donne_acces_aux_retours(string $role): void
    {
        $response = $this->actingAs($this->staff($role))->get(route('admin.dashboard'));

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'id="adminSidebar"');
        $this->assertNotFalse($start, 'La barre latérale est absente du tableau de bord.');

        $end = strpos($html, '</aside>', $start);
        $this->assertNotFalse($end, 'La barre latérale n\'est pas refermée.');

        $sidebar = substr($html, $start, $end - $start);

        $this->assertStringContainsString(route('agent.return.index'), $sidebar);
        $this->assertStringContainsString(__('admin.returns'), $sidebar);
    }

    /** @return array<string, array{string}> */
    public static function roles(): array
    {
        return [
            'agent d\'accueil' => ['agent_accueil'],
            'gestionnaire' => ['gestionnaire'],
            'super admin' => ['super_admin'],
        ];
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
