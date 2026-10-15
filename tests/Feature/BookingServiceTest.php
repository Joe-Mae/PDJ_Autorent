<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\BookingBlock;
use App\Models\User;
use App\Services\BookingService;
use App\Services\ReferenceGenerator;
use App\Support\BookingPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les garde-fous de BookingService.
 *
 * Ce service concentre les règles qui protègent l'agence d'une réservation
 * fantôme et d'une double réservation : c'est le fichier le plus critique du
 * projet, donc le plus testé. Les montants sont figés à la création, et les
 * tests le vérifient montant par montant.
 */
class BookingServiceTest extends TestCase
{
    // ── Création ─────────────────────────────────────────────────────────────

    public function test_creation_fige_le_prix_de_la_periode_demandee(): void
    {
        $vehicle = $this->vehicle();

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => false,
        ]);

        // 4 jours × 18 000 F CFA. La caution ne fait pas partie du total :
        // elle est versée et rendue, elle n'est pas une recette.
        $this->assertSame(4, $booking->days);
        $this->assertSame(72000, $booking->price_rental);
        $this->assertSame(0, $booking->price_driver);
        $this->assertSame(72000, $booking->price_total);
        $this->assertSame(60000, $booking->deposit_amount);
    }

    public function test_creation_facture_le_chauffeur_avec_la_voiture_par_jour(): void
    {
        $this->driver();
        $vehicle = $this->vehicle();

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => true,
        ]);

        // Le tarif voiture + chauffeur est de 5 000 F CFA par jour, quel que
        // soit le nombre d'heures conduites dans la journée.
        $this->assertSame(12, $booking->driver_blocks);
        $this->assertSame(44, $booking->driver_hours);
        $this->assertSame(5000, $booking->driver_daily_price);
        $this->assertSame(4 * 5000, $booking->price_driver);
        $this->assertSame(72000 + 20000, $booking->price_total);
    }

    public function test_le_prix_ne_bouge_pas_quand_le_tarif_change_apres_la_creation(): void
    {
        $vehicle = $this->vehicle();

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => false,
        ]);

        $vehicle->category->update(['daily_price' => 30000]);
        $booking->refresh();

        $this->assertSame(72000, $booking->price_total);
    }

    public function test_creation_enregistre_une_audite(): void
    {
        $vehicle = $this->vehicle();

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => null,
            'with_driver' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'booking.created']);
    }

    // ── Double réservation ───────────────────────────────────────────────────

    public function test_refuse_un_vehicule_deja_reserve_sur_la_meme_periode(): void
    {
        $vehicle = $this->vehicle();
        $period = $this->period(4);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);
    }

    public function test_refuse_un_vehicule_restitue_moins_de_deux_heures_avant_le_nouveau_depart(): void
    {
        $vehicle = $this->vehicle();

        // Première location : J+1 8 h → J+2 12 h.
        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->periodOnDays(1, 1, 8, 12),
            'user' => null,
            'with_driver' => false,
        ]);

        // Nouvelle demande : J+2 13 h, soit une heure après la restitution.
        // Le véhicule doit être contrôlé et nettoyé : on refuse.
        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->periodOnDays(1, 2, 13, 18),
            'user' => null,
            'with_driver' => false,
        ]);
    }

    public function test_accepte_un_vehicule_restitue_apres_le_battement_de_preparation(): void
    {
        $vehicle = $this->vehicle();

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->periodOnDays(1, 1, 8, 12),
            'user' => null,
            'with_driver' => false,
        ]);

        // J+2 15 h : trois heures de battement, le véhicule a été préparé.
        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->periodOnDays(1, 2, 15, 18),
            'user' => null,
            'with_driver' => false,
        ]);

        $this->assertSame(BookingStatus::EnAttente, $booking->status);
    }

    public function test_refuse_un_vehicule_bloque_par_la_maintenance(): void
    {
        $vehicle = $this->vehicle();
        $period = $this->period(4);

        BookingBlock::query()->create([
            'vehicle_id' => $vehicle->id,
            'starts_at' => $period->startsAt,
            'ends_at' => $period->endsAt,
        ]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);
    }

    public function test_refuse_un_vehicule_hors_service(): void
    {
        $vehicle = $this->vehicle(null, ['is_active' => false]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => null,
            'with_driver' => false,
        ]);
    }

    public function test_accepte_un_reservation_anulee_sur_la_meme_periode(): void
    {
        $vehicle = $this->vehicle();
        $period = $this->period(4);

        $cancelled = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);

        $this->bookings()->cancel($cancelled, $this->staff());

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => null,
            'with_driver' => false,
        ]);

        $this->assertSame(BookingStatus::EnAttente, $booking->status);
    }

    // ── Admissibilité du client ──────────────────────────────────────────────

    public function test_refuse_un_client_bloque_qui_n_a_pas_de_caution_d_avance(): void
    {
        $vehicle = $this->vehicle();
        $blocked = $this->customer(['blocked_at' => now(), 'no_show_count' => 2]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => $blocked,
            'with_driver' => false,
        ]);
    }

    public function test_accepte_un_client_bloque_qui_a_accepte_la_caution_d_avance(): void
    {
        $vehicle = $this->vehicle();
        $customer = $this->customer([
            'blocked_at' => now(),
            'no_show_count' => 2,
            'prepayment_required' => true,
        ]);

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => $customer,
            'with_driver' => false,
        ]);

        $this->assertSame(BookingStatus::EnAttente, $booking->status);
    }

    public function test_refuse_un_client_qui_a_atteint_son_nombre_maximal_de_reservations(): void
    {
        $vehicle = $this->vehicle();
        $customer = $this->customer();
        $period = $this->period(2);

        // Le maximum par défaut est de 2 réservations actives.
        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2, 1),
            'user' => $customer,
            'with_driver' => false,
        ]);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2, 5),
            'user' => $customer,
            'with_driver' => false,
        ]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $period,
            'user' => $customer,
            'with_driver' => false,
        ]);
    }

    public function test_refuse_un_client_moins_de_18_ans(): void
    {
        $vehicle = $this->vehicle();
        $minor = $this->customer(['birth_date' => now()->subYears(16)->toDateString()]);

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => $minor,
            'with_driver' => false,
        ]);
    }

    public function test_un_client_de_18_ans_pile_est_admis(): void
    {
        $vehicle = $this->vehicle();
        $justEighteen = $this->customer(['birth_date' => now()->subYears(18)->toDateString()]);

        $booking = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(2),
            'user' => $justEighteen,
            'with_driver' => false,
        ]);

        $this->assertSame(BookingStatus::EnAttente, $booking->status);
    }

    // ── Chauffeur ────────────────────────────────────────────────────────────

    public function test_refuse_le_chauffeur_quand_aucun_chauffeur_n_est_dans_le_planning(): void
    {
        $period = $this->period(2);

        // Aucun chauffeur actif : le planning est vide, l'offre est nulle.
        $this->assertFalse($this->availability()->canOfferDriver($period));

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => true,
        ]);
    }

    public function test_refuse_le_chauffeur_quand_le_unique_chauffeur_est_deja_affecte(): void
    {
        $this->driver();
        $period = $this->period(2);

        // Le seul chauffeur part sur une première location, sur les mêmes dates.
        $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => true,
        ]);

        $this->assertFalse($this->availability()->canOfferDriver($period));

        $this->expectException(BookingException::class);

        $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => true,
        ]);
    }

    public function test_propose_le_chauffeur_a_la_seconde_place_libre(): void
    {
        $this->driver();
        $this->driver();
        $period = $this->period(2);

        $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => true,
        ]);

        $this->assertSame(1, $this->availability()->freeDriverSlots($period));

        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $period,
            'user' => null,
            'with_driver' => true,
        ]);

        $this->assertSame(0, $this->availability()->freeDriverSlots($period));
        $this->assertTrue($booking->with_driver);
    }

    public function test_un_chauffeur_en_conge_ne_compte_pas_dans_le_planning(): void
    {
        $this->driver(['status' => DriverStatus::Conge]);
        $period = $this->period(2);

        $this->assertSame(0, $this->availability()->freeDriverSlots($period));
    }

    // ── Cycle de vie ─────────────────────────────────────────────────────────

    public function test_confirmer_puis_demarrer_puis_terminer(): void
    {
        $agent = $this->staff();
        $booking = $this->pendingBooking();

        $confirmed = $this->bookings()->confirm($booking, $agent);
        $this->assertSame(BookingStatus::Confirmee, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);

        $started = $this->bookings()->start($confirmed, $agent, 42_000, 'full');
        $this->assertSame(BookingStatus::EnCours, $started->status);
        $this->assertSame(42_000, $started->mileage_at_pickup);
        $this->assertSame('full', $started->fuel_level_pickup);

        $finished = $this->bookings()->finish($started, $agent, 43_250, 'full');
        $this->assertSame(BookingStatus::Terminee, $finished->status);
        $this->assertSame(43_250, $finished->mileage_at_return);
    }

    public function test_refuse_de_confirmer_si_le_vehicule_est_devenu_indisponible(): void
    {
        $agent = $this->staff();
        $vehicle = $this->vehicle();
        $period = $this->period(4);
        $referenceGenerator = app(ReferenceGenerator::class);
        $dailyPrice = (int) $vehicle->category->daily_price;
        $bookingAttributes = [
            'user_id' => null,
            'vehicle_id' => $vehicle->id,
            'category_id' => $vehicle->category_id,
            'starts_at' => $period->startsAt,
            'ends_at' => $period->endsAt,
            'days' => $period->days(),
            'pickup_hour' => 8,
            'return_hour' => 19,
            'with_driver' => false,
            'driver_blocks' => 0,
            'driver_hours' => 0,
            'daily_price' => $dailyPrice,
            'price_rental' => $dailyPrice * $period->days(),
            'price_driver' => 0,
            'price_total' => $dailyPrice * $period->days(),
            'deposit_amount' => $vehicle->category->deposit(),
            'currency' => 'XOF',
            'holiday_days' => 0,
            'status' => BookingStatus::EnAttente,
            'public_token' => bin2hex(random_bytes(20)),
        ];
        $booking = Booking::query()->create([
            ...$bookingAttributes,
            'reference' => $referenceGenerator->generate(),
        ]);
        Booking::query()->create([
            ...$bookingAttributes,
            'reference' => $referenceGenerator->generate(),
            'public_token' => bin2hex(random_bytes(20)),
        ]);

        $this->expectException(BookingException::class);

        $this->bookings()->confirm($booking, $agent);
    }

    public function test_refuse_de_demarrer_une_reservation_non_confirmee(): void
    {
        $agent = $this->staff();
        $booking = $this->pendingBooking();

        $this->expectException(BookingException::class);

        $this->bookings()->start($booking, $agent);
    }

    public function test_refuse_de_confirmer_une_reservation_annulee(): void
    {
        $agent = $this->staff();
        $booking = $this->bookings()->cancel($this->pendingBooking(), $agent);

        $this->expectException(BookingException::class);

        $this->bookings()->confirm($booking, $agent);
    }

    public function test_une_absence_de_cremente_le_compteur_et_bloque_au_second(): void
    {
        $customer = $this->customer();

        $first = $this->pendingBooking($customer);
        $this->bookings()->markAsNoShow($first);

        $this->assertSame(1, $first->user->refresh()->no_show_count);
        $this->assertNull($first->user->blocked_at, 'Une absence ne bloque pas encore le compte.');

        $second = $this->pendingBooking($customer);
        $this->bookings()->markAsNoShow($second);

        $this->assertSame(2, $second->user->refresh()->no_show_count);
        $this->assertNotNull($second->user->blocked_at, 'Deux absences bloquent le compte.');
    }

    // ── Redé-datement ────────────────────────────────────────────────────────

    public function test_redater_recalcule_le_prix_et_conserve_la_reference(): void
    {
        $booking = $this->pendingBooking();
        $original = $booking->reference;

        $reprice = $this->bookings()->reprice($booking, $this->period(6), false);

        $this->assertSame($original, $reprice->reference);
        $this->assertSame(6, $reprice->days);
        $this->assertSame(6 * self::DAILY_PRICE, $reprice->price_total);
    }

    public function test_redater_sur_sa_propre_periode_est_accepte(): void
    {
        $booking = $this->pendingBooking();

        // Sans cela, une réservation se confondrait avec elle-même et aucun
        // changement de dates ne serait jamais possible.
        $reprice = $this->bookings()->reprice($booking, $this->period(4), false);

        $this->assertSame(4, $reprice->days);
    }

    public function test_redater_vers_un_creneau_occupe_par_un_autre_client_est_refuse(): void
    {
        $vehicle = $this->vehicle();

        $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => false,
        ]);

        // Une seconde réservation, plus loin, sur le même véhicule.
        $second = $this->bookings()->create([
            'vehicle' => $vehicle,
            'period' => $this->period(4, 10),
            'user' => null,
            'with_driver' => false,
        ]);

        $this->expectException(BookingException::class);

        $this->bookings()->reprice($second, $this->period(4), false);
    }

    // ── Montants ─────────────────────────────────────────────────────────────

    public function test_le_montant_a_emporter_rejoint_loye_et_caution(): void
    {
        $booking = $this->pendingBooking();

        // 4 jours × 18 000 = 72 000 de location, plus 60 000 de caution à
        // laisser à l'agence. C'est la somme que le client doit avoir sur lui.
        $this->assertSame(132000, $booking->amountToCarry());
    }

    public function test_le_montant_a_emporter_inclut_le_chauffeur_mais_pas_la_caution_en_double(): void
    {
        $this->driver();

        $booking = $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(4),
            'user' => null,
            'with_driver' => true,
        ]);

        // 72 000 de location + 20 000 de chauffeur + 60 000 de caution.
        $this->assertSame(152000, $booking->amountToCarry());
    }

    // ── Transitions interdites ───────────────────────────────────────────────

    public static function transitionsInvalides(): array
    {
        return [
            'confirmer une location déjà démarrée' => [BookingStatus::EnCours, BookingStatus::Confirmee],
            'terminer une réservation encore en attente' => [BookingStatus::EnAttente, BookingStatus::Terminee],
            'refuser une réservation déjà confirmée' => [BookingStatus::Confirmee, BookingStatus::Refusee],
        ];
    }

    #[DataProvider('transitionsInvalides')]
    public function test_refuse_une_transition_qui_saute_une_etape(BookingStatus $from, BookingStatus $to): void
    {
        $agent = $this->staff();
        $booking = $this->pendingBooking();
        $booking->update(['status' => $from]);

        $this->expectException(BookingException::class);

        match ($to) {
            BookingStatus::Confirmee => $this->bookings()->confirm($booking, $agent),
            BookingStatus::Terminee => $this->bookings()->finish($booking, $agent),
            BookingStatus::Refusee => $this->bookings()->refuse($booking, $agent, 'Refusée.'),
            default => null,
        };
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    /** Créneau à des heures libres, pour tester le battement de préparation. */
    protected function periodOnDays(int $days, int $fromNow, int $fromHour, int $toHour): BookingPeriod
    {
        $startsAt = now()->addDays($fromNow)->startOfDay();

        return BookingPeriod::make(
            $startsAt->toDateString(),
            $startsAt->copy()->addDays($days)->toDateString(),
            $fromHour,
            $toHour,
        );
    }

    /** Réservation d'attente sur un véhicule neuf, pour ce client s'il est donné. */
    protected function pendingBooking(?User $customer = null): Booking
    {
        return $this->bookings()->create([
            'vehicle' => $this->vehicle(),
            'period' => $this->period(4),
            'user' => $customer,
            'with_driver' => false,
        ]);
    }
}
