@php
    /**
     * Le seul appel à l'action de la fiche. Un bouton, pas deux : « Réserver »
     * mène directement à l'étape 1 du tunnel avec les dates déjà figées.
     *
     * @var bool $sticky
     */
    $sticky = $sticky ?? false;
    $money = fn (int $amount) => app(\App\Services\SettingsService::class)->money($amount);
    $query = $quote ? array_filter([
        'avec_chauffeur' => $quote->withDriver ? 1 : null,
    ], fn ($value) => $value !== null) : [];
@endphp

@if (! $period)
    <div class="{{ $sticky ?? false ? 'sticky-book' : 'pdj-card' }}">
        <a class="btn btn-primary {{ $sticky ?? false ? 'w-100' : 'btn-lg w-100' }}"
           href="{{ route('booking.guest.create', ['locale' => $locale, 'vehicle' => $vehicle]) }}">
            {{ __('general.book') }}
        </a>
    </div>
@else
@if (! $isFree)
    <div class="alert alert-warning">
        <i class="bi bi-calendar-x me-1"></i>{{ __('vehicle.not_free') }}
    </div>
@elseif ($sticky)
    <div class="sticky-book">
        <div>
            <div class="label">{{ $quote->days }} {{ __('general.day_short') }}{{ $quote->days > 1 ? 's' : '' }}</div>
            <div class="amount">{{ $money($quote->priceTotal) }}</div>
        </div>
        <a class="btn btn-primary w-100"
           href="{{ route('booking.guest.create', [
               'locale' => $locale,
               'vehicle' => $vehicle,
               'du' => $period->startsAtDate(),
               'au' => $period->endsAtDate(),
               'heure_depart' => $period->startsAtHour(),
               'heure_retour' => $period->endsAtHour(),
               'avec_chauffeur' => $quote->withDriver ? 1 : null,
           ]) }}">{{ __('general.book') }}</a>
    </div>
@else
    <div class="pdj-card">
        <div class="section-title">{{ __('vehicle.period') }}</div>
        <p class="small mb-2">
            {{ $period->startsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->startsAtHour()) }}
            <i class="bi bi-arrow-right mx-1"></i>
            {{ $period->endsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->endsAtHour()) }}
        </p>

        @if ($quote->withDriver)
            <div class="driver-option mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge"></i>
                    <strong class="small">{{ __('driver.badge') }}</strong>
                </div>
                <div class="hint mt-1">
                    {{ $quote->days }} {{ __('general.days') }} ×
                    {{ $money($quote->driverDailyPrice) }} —
                    {{ $money($quote->priceDriver) }}
                </div>
            </div>
        @endif

        <a class="btn btn-primary btn-lg w-100"
           href="{{ route('booking.guest.create', [
               'locale' => $locale,
               'vehicle' => $vehicle,
               'du' => $period->startsAtDate(),
               'au' => $period->endsAtDate(),
               'heure_depart' => $period->startsAtHour(),
               'heure_retour' => $period->endsAtHour(),
               'avec_chauffeur' => $quote->withDriver ? 1 : null,
           ]) }}">{{ __('general.book') }}</a>

        <p class="small text-muted text-center mt-2 mb-0">{{ __('general.no_online_payment') }}</p>
    </div>
@endif
@endif
