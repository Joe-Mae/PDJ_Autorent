<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Créneau de location, avec le calcul des journees et des blocs de chauffeur.
 *
 * Raison d'être : le calcul des blocs de 4 h est le point où il est le plus
 * facile de se tromper. On l'isole ici pour qu'il soit testable tout seul et
 * qu'il n'y ait qu'un endroit où lire la règle.
 *
 * Rappel de la règle : l'arrondi se fait **par jour calendaire**, jamais sur le
 * total. Un client qui loue 3 jours de 2 h par jour paie 3 blocs, pas 2.
 */
final readonly class BookingPeriod
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public int $openHour,
        public int $closeHour,
    ) {}

    public static function make(
        string $startDate,
        string $endDate,
        int $pickupHour,
        int $returnHour,
        int $openHour = 8,
        int $closeHour = 19,
    ): self {
        return new self(
            Date::parse($startDate)->startOfDay()->setTime($pickupHour, 0)->toImmutable(),
            Date::parse($endDate)->startOfDay()->setTime($returnHour, 0)->toImmutable(),
            $openHour,
            $closeHour,
        );
    }

    public static function fromDates(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $openHour = 8,
        int $closeHour = 19,
    ): self {
        return new self($startsAt, $endsAt, $openHour, $closeHour);
    }

    /** Nombre de jours calendaires de location, minimum 1. */
    public function days(): int
    {
        return max(1, $this->startsAt->startOfDay()->diffInDays($this->endsAt->startOfDay()));
    }

    public function isSingleDay(): bool
    {
        return $this->startsAt->isSameDay($this->endsAt);
    }

    /**
     * Heures de conduite facturables, jour par jour.
     *
     * @return list<int>
     */
    public function dailyHours(): array
    {
        if ($this->isSingleDay()) {
            return [max(0, $this->endsAt->hour - $this->startsAt->hour)];
        }

        $hours = [$this->closeHour - $this->startsAt->hour];

        for ($day = 1; $day < $this->days() - 1; $day++) {
            $hours[] = $this->closeHour - $this->openHour;
        }

        $hours[] = max(0, $this->endsAt->hour - $this->openHour);

        return $hours;
    }

    /** Heures cumulées de conduite. */
    public function totalHours(): int
    {
        return array_sum($this->dailyHours());
    }

    /**
     * Blocs de 4 h facturés : arrondi au bloc supérieur, jour par jour.
     *
     * @return list<int> nombre de blocs facturés pour chaque jour
     */
    public function dailyDriverBlocks(int $blockHours): array
    {
        return array_map(
            static fn (int $hours) => (int) ceil(max(0, $hours) / max(1, $blockHours)),
            $this->dailyHours(),
        );
    }

    public function totalDriverBlocks(int $blockHours): int
    {
        return array_sum($this->dailyDriverBlocks($blockHours));
    }

    /** Heures réellement conduites, pour afficher « environ 8 h / jour ». */
    public function averageDailyHours(): int
    {
        return (int) round($this->totalHours() / $this->days());
    }

    /** Chaque jour calendaire couvert, pour le calcul des jours fériés. */
    /** @return list<CarbonImmutable> */
    public function eachDay(): array
    {
        $days = [];
        $cursor = $this->startsAt->startOfDay();

        while ($cursor->lessThanOrEqualTo($this->endsAt->startOfDay())) {
            $days[] = $cursor;
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    /**
     * Le même créneau, repoussé pour englober le battement entre deux locations.
     * C'est cette version-là qu'on oppose aux réservations existantes.
     */
    public function padded(int $turnaroundHours): self
    {
        if ($turnaroundHours <= 0) {
            return $this;
        }

        return new self(
            $this->startsAt->subHours($turnaroundHours),
            $this->endsAt->addHours($turnaroundHours),
            $this->openHour,
            $this->closeHour,
        );
    }

    /** Le créneau chevauche-t-il un autre ? Test ouvert, comme le contrat métier. */
    public function overlaps(self $other): bool
    {
        return $this->startsAt->lt($other->endsAt) && $this->endsAt->gt($other->startsAt);
    }

    public function isInThePast(): bool
    {
        return $this->endsAt->isPast();
    }

    /** Heures choisies hors de la plage d'ouverture de l'agence. */
    public function isOutsideOpeningHours(): bool
    {
        if ($this->startsAt->hour < $this->openHour || $this->startsAt->hour > $this->closeHour) {
            return true;
        }

        return $this->endsAt->hour < $this->openHour || $this->endsAt->hour > $this->closeHour;
    }

    public function startsAtHour(): int
    {
        return $this->startsAt->hour;
    }

    public function endsAtHour(): int
    {
        return $this->endsAt->hour;
    }

    public function startsAtDate(): string
    {
        return $this->startsAt->toDateString();
    }

    public function endsAtDate(): string
    {
        return $this->endsAt->toDateString();
    }
}
