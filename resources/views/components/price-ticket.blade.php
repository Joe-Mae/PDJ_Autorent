@php
    /**
     * Le décompte, façon ticket de caisse.
     *
     * C'est la pièce la plus importante du tunnel. Elle doit faire comprendre
     * en un coup d'œil deux choses : ce que coûte la location, et combien il faut
     * sortir de son portefeuille le matin du retrait. C'est pour ça que la
     * caution est à part, puis que le total « à avoir sur soi » est mis en
     * couleur — c'est le chiffre que le client retient.
     *
     * @var \App\Support\PriceQuote $quote
     */
    $locale = app()->getLocale();
    $money = fn (int $amount) => number_format($amount, 0, ' ', ' ').' FCFA';
@endphp

<div class="price-ticket">
    @foreach ($quote->receiptLines() as $line)
        <div class="price-ticket__row">
            <span>{{ $line['label'] }}</span>
            <span class="fw-semibold text-nowrap">{{ $money($line['amount']) }}</span>
        </div>
    @endforeach

    <div class="price-ticket__sep"></div>

    <div class="price-ticket__total">
        <span>{{ __('general.pay_at_agency') }}</span>
        <span>{{ $money($quote->priceTotal) }}</span>
    </div>

    <div class="price-ticket__row price-ticket__deposit">
        <span>{{ __('general.deposit_to_prepare') }}</span>
        <span class="text-nowrap">+ {{ $money($quote->deposit) }}</span>
    </div>

    <div class="price-ticket__carry">
        <div class="label">{{ __('general.carry_on_day') }}</div>
        <div class="amount">{{ $money($quote->amountToCarry) }}</div>
    </div>
</div>
