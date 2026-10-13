<?php

namespace Tests\Unit;

use App\Support\BookingPeriod;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Le calcul des blocs de chauffeur est la règle la plus facile à gotten de
 * travers, et la plus coûteuse quand elle l'est : c'est elle qui décide de ce
 * que le client croit devoir. Elle est donc testée toute seule, sans base de
 * données, avec des cas qu'on peut lire d'un coup d'œil.
 */
class BookingPeriodTest extends TestCase
{
    public function test_compte_les_jours_calendaires(): void
    {
        // Du 12 au 18 : six nuits, donc six jours facturés.
        $period = BookingPeriod::make('2026-03-12', '2026-03-18', 8, 19);

        $this->assertSame(6, $period->days());
    }

    public function test_une_journee_vaut_au_moins_un_jour(): void
    {
        $period = BookingPeriod::make('2026-03-12', '2026-03-12', 8, 19);

        $this->assertTrue($period->isSingleDay());
        $this->assertSame(1, $period->days());
    }

    public function test_arrondi_au_bloc_superieur_par_jour(): void
    {
        // 8h -> 19h = 11 h = 3 blocs (2 blocs ne couvriraient que 8 h).
        $period = BookingPeriod::make('2026-03-12', '2026-03-12', 8, 19);

        $this->assertSame([11], $period->dailyHours());
        $this->assertSame([3], $period->dailyDriverBlocks(4));
    }

    public function test_une_demi_journee_ne_compte_qu_un_bloc_par_jour(): void
    {
        // 12/03 8h -> 14/03 12h : deux jours de location.
        // 1er jour : la voiture sort à la fermeture, 11 h = 3 blocs.
        // 2e jour : prise au lever, rendue à midi, 4 h = 1 bloc.
        $period = BookingPeriod::make('2026-03-12', '2026-03-14', 8, 12);

        $this->assertSame([11, 4], $period->dailyHours());
        $this->assertSame([3, 1], $period->dailyDriverBlocks(4));
        $this->assertSame(4, $period->totalDriverBlocks(4));
    }

    public function test_les_jours_intermediaires_prennent_toute_la_journee(): void
    {
        // Départ 12/03 à 9h, retour 15/03 à 17h : trois jours de location.
        // 1er jour : 9h->19h = 10 h | jours suivants : 11 h | dernier : 8h->17h = 9 h.
        $period = BookingPeriod::make('2026-03-12', '2026-03-15', 9, 17);

        $this->assertSame(3, $period->days());
        $this->assertSame([10, 11, 9], $period->dailyHours());
        $this->assertSame(30, $period->totalHours());
    }

    public function test_le_battement_elargit_le_creneau(): void
    {
        $period = BookingPeriod::make('2026-03-12', '2026-03-12', 8, 19);
        $padded = $period->padded(2);

        $this->assertSame(
            $period->startsAt->subHours(2)->toDateTimeString(),
            $padded->startsAt->toDateTimeString()
        );
        $this->assertSame(
            $period->endsAt->addHours(2)->toDateTimeString(),
            $padded->endsAt->toDateTimeString()
        );
    }

    public function test_un_battement_nul_ne_change_rien(): void
    {
        $period = BookingPeriod::make('2026-03-12', '2026-03-12', 8, 19);

        $this->assertSame(
            $period->startsAt->toDateTimeString(),
            $period->padded(0)->startsAt->toDateTimeString()
        );
    }

    #[DataProvider('overlapCases')]
    public function test_chevauchement(string $debutA, string $finA, string $debutB, string $finB, bool $attendu): void
    {
        $a = BookingPeriod::make($debutA, $finA, 8, 19);
        $b = BookingPeriod::make($debutB, $finB, 8, 19);

        $this->assertSame($attendu, $a->overlaps($b));
    }

    /** @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: bool}> */
    public static function overlapCases(): array
    {
        return [
            // Deux locations qui se touchent la MÊME journée se chevauchent :
            // la première rend la voiture le soir, la seconde la prend le matin.
            'fin et depart le meme jour' => ['2026-03-12', '2026-03-15', '2026-03-15', '2026-03-18', true],
            'depart et fin le meme jour, en sens inverse' => ['2026-03-12', '2026-03-15', '2026-03-09', '2026-03-12', true],
            'chevauchement partiel' => ['2026-03-12', '2026-03-15', '2026-03-14', '2026-03-18', true],
            'chevauchement complet' => ['2026-03-12', '2026-03-18', '2026-03-14', '2026-03-15', true],
            'contenu dans l autre' => ['2026-03-12', '2026-03-20', '2026-03-14', '2026-03-15', true],
            'periodes disjointes' => ['2026-03-01', '2026-03-05', '2026-03-10', '2026-03-12', false],
            'separation d un seul jour' => ['2026-03-01', '2026-03-05', '2026-03-06', '2026-03-10', false],
        ];
    }

    public function test_deux_locations_se_touchant_a_la_meme_heure_ne_se_chevauchent_pas(): void
    {
        // Fin à 19h, départ suivant à 19h : la borne est ouverte des deux côtés.
        $a = BookingPeriod::fromDates(
            Date::parse('2026-03-12 08:00')->toImmutable(),
            Date::parse('2026-03-15 19:00')->toImmutable(),
        );
        $b = BookingPeriod::fromDates(
            Date::parse('2026-03-15 19:00')->toImmutable(),
            Date::parse('2026-03-18 19:00')->toImmutable(),
        );

        $this->assertFalse($a->overlaps($b));
    }

    public function test_refuse_les_heures_hors_des_horaires_d_ouverture(): void
    {
        $tropTot = BookingPeriod::make('2026-03-12', '2026-03-12', 6, 19);
        $tropTard = BookingPeriod::make('2026-03-12', '2026-03-12', 8, 21);

        $this->assertTrue($tropTot->isOutsideOpeningHours());
        $this->assertTrue($tropTard->isOutsideOpeningHours());
        $this->assertFalse(BookingPeriod::make('2026-03-12', '2026-03-12', 8, 19)->isOutsideOpeningHours());
    }
}
