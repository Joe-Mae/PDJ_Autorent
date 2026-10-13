<?php

namespace App\Support;

/**
 * Décompte d'une réservation, prêt à être affiché au client tel quel.
 *
 * L'ordre des lignes est celui du « ticket » : location, chauffeur, total à payer
 * à l'agence, puis la caution à part, puis ce qu'il faut avoir sur soi.
 */
final readonly class PriceQuote
{
    /**
     * @param  list<array{date: string, label: string, price: int, surcharge: int}>  $lines
     */
    public function __construct(
        public int $days,
        public int $dailyPrice,
        public int $priceRental,
        public bool $withDriver,
        public int $driverBlocks,
        public int $driverBlockHours,
        public int $driverHours,
        public int $driverDailyPrice,
        public int $priceDriver,
        public int $priceTotal,
        public int $deposit,
        public int $amountToCarry,
        public array $lines = [],
        public int $holidayDays = 0,
        public ?string $appliedRule = null,
        public bool $legacyDriverPricing = false,
    ) {}

    /** Chauffeur proposé par défaut quand la durée dépasse le seuil paramétré. */
    public function suggestDriver(int $thresholdDays): bool
    {
        return $this->days >= $thresholdDays;
    }

    /** Même décompte, chauffeur inversé — sert au recalcul en direct dans le tunnel. */
    public function withDriver(bool $withDriver): self
    {
        if ($withDriver === $this->withDriver) {
            return $this;
        }

        $priceDriver = $withDriver ? $this->days * $this->driverDailyPrice : 0;

        return new self(
            $this->days,
            $this->dailyPrice,
            $this->priceRental,
            $withDriver,
            $this->driverBlocks,
            $this->driverBlockHours,
            $this->driverHours,
            $this->driverDailyPrice,
            $priceDriver,
            $this->priceRental + $priceDriver,
            $this->deposit,
            $this->priceRental + $priceDriver + $this->deposit,
            $this->lines,
            $this->holidayDays,
            $this->appliedRule,
            $this->legacyDriverPricing,
        );
    }

    /** @return array<string, int|null> */
    public function toAmounts(): array
    {
        return [
            'days' => $this->days,
            'daily_price' => $this->dailyPrice,
            'price_rental' => $this->priceRental,
            'driver_blocks' => $this->driverBlocks,
            'driver_hours' => $this->driverHours,
            'driver_daily_price' => $this->withDriver ? $this->driverDailyPrice : null,
            'price_driver' => $this->priceDriver,
            'price_total' => $this->priceTotal,
            'deposit_amount' => $this->deposit,
            'holiday_days' => $this->holidayDays,
        ];
    }

    /** Lignes telles qu'affichées sur le bon de réservation PDF. */
    /** @return list<array{label: string, amount: int}> */
    public function receiptLines(): array
    {
        $lines = [['label' => __('quote.line.rental', ['days' => $this->days]), 'amount' => $this->priceRental]];

        if ($this->withDriver) {
            $lines[] = [
                'label' => $this->legacyDriverPricing
                    ? __('booking.line.driver', ['blocks' => $this->driverBlocks])
                    : __('quote.line.driver', ['days' => $this->days]),
                'amount' => $this->priceDriver,
            ];
        }

        return $lines;
    }
}
