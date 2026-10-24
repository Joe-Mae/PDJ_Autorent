@php
    /**
     * Une ligne de résultat.
     *
     * Liste et pas grille de cartes : on compare des prix, des places et des
     * boîtes, pas des vignettes. La ligne porte l'essentiel — photo, nom,
     * caractéristiques, prix de la période, caution — et un seul lien.
     *
     * @var \App\Models\Vehicle $vehicle
     * @var \App\Support\BookingPeriod $period
     * @var bool $withDriver
     */
    $locale = app()->getLocale();
    $money = fn (int $amount) => app(\App\Services\SettingsService::class)->money($amount);
    $quote = app(\App\Services\PricingService::class)->quote($vehicle->category, $period, $withDriver);
    $params = request()->query();
    $bookingParams = [
        'locale' => $locale,
        'vehicle' => $vehicle,
        'du' => $period->startsAtDate(),
        'au' => $period->endsAtDate(),
        'heure_depart' => $period->startsAtHour(),
        'heure_retour' => $period->endsAtHour(),
        'avec_chauffeur' => $withDriver ? 1 : null,
    ];
@endphp

<div class="vehicle-row">
    <a class="vehicle-row__photo" href="{{ route('vehicles.show', ['locale' => $locale, 'vehicle' => $vehicle]) }}?{{ http_build_query($params) }}"
       tabindex="-1" aria-hidden="true">
        <img src="{{ $vehicle->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
             alt="" loading="lazy" width="336" height="236">
    </a>

    <div class="flex-grow-1 d-flex flex-column">
        <h3 class="vehicle-row__name">
            <a class="text-decoration-none text-reset"
               href="{{ route('vehicles.show', ['locale' => $locale, 'vehicle' => $vehicle]) }}?{{ http_build_query($params) }}">
                {{ $vehicle->displayName() }}
            </a>
        </h3>

        <div class="text-muted small">{{ $vehicle->category->name($locale) }} · {{ $vehicle->year }}</div>

        <div class="vehicle-row__specs">
            <span><i class="bi bi-gear me-1"></i>{{ $vehicle->transmissionLabel() }}</span>
            <span><i class="bi bi-fuel-pump me-1"></i>{{ $vehicle->fuelLabel() }}</span>
            <span><i class="bi bi-people me-1"></i>{{ $vehicle->seats }} {{ __('vehicle.seats') }}</span>
            <span><i class="bi bi-bag me-1"></i>{{ $vehicle->bags }} {{ __('vehicle.bags') }}</span>
            @if ($vehicle->air_conditioning)
                <span><i class="bi bi-snow me-1"></i>{{ __('vehicle.air_con') }}</span>
            @endif
        </div>

        <div class="mt-auto">
            <span class="badge-assurance"><i class="bi bi-shield-check me-1"></i>{{ __('general.reassurance.insurance') }}</span>
            @unless ($withDriver)
                <span class="badge-assurance ms-1">{{ __('general.identity_only') }}</span>
            @endunless
        </div>
    </div>

    <div class="vehicle-row__price">
        <div class="amount">{{ $money($quote->priceTotal) }}</div>
        @if ($withDriver)
            <div class="small text-muted">
                {{ __('quote.line.rental', ['days' => $quote->days]) }} : {{ $money($quote->priceRental) }}<br>
                {{ __('quote.line.driver', ['days' => $quote->days]) }} : {{ $money($quote->priceDriver) }}
            </div>
        @endif
        <div class="per-day">
            {{ __('general.per_day', ['price' => $money($quote->dailyPrice)]) }}<br>
            {{ __('general.for_period', ['days' => $quote->days]) }}
        </div>
        <div class="deposit">{{ __('general.deposit', ['price' => $money($quote->deposit)]) }}</div>
        <a class="btn btn-primary btn-sm w-100 mt-2"
           href="{{ route('booking.guest.create', $bookingParams) }}">
            {{ __('general.book') }}
        </a>
    </div>
</div>
