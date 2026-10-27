<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

/**
 * Chaque statut doit se lire d'un coup d'œil.
 *
 * Régression : le thème réutilisait le vert de la marque pour « confirmée »,
 * « en cours » et « terminée » — trois étapes différentes, une seule couleur.
 * Un refus se confondait donc avec le reste de la liste.
 */
class BookingStatusColorTest extends TestCase
{
    public function test_chaque_statut_porte_une_couleur_distincte(): void
    {
        $colors = array_map(
            static fn (BookingStatus $status): string => $status->color(),
            BookingStatus::cases(),
        );

        $this->assertSame(
            $colors,
            array_values(array_unique($colors)),
            'Deux statuts partagent la même couleur de badge.',
        );
    }

    public function test_les_issues_defavorables_ne_sont_pas_vertes(): void
    {
        $this->assertSame('refusee', BookingStatus::Refusee->color());
        $this->assertSame('annulee', BookingStatus::Annulee->color());
        $this->assertSame('absence', BookingStatus::NonPresente->color());
    }

    public function test_une_location_en_cours_se_distingue_d_une_reservation_confirmee(): void
    {
        $this->assertSame('encours', BookingStatus::EnCours->color());
        $this->assertSame('confirmee', BookingStatus::Confirmee->color());
        $this->assertNotSame(BookingStatus::Confirmee->color(), BookingStatus::EnCours->color());
    }
}
