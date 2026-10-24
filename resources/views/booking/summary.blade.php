@extends('layouts.app')

@section('title', __('booking.step.summary'))
@section('description', __('general.meta_description'))

@section('content')

<div class="container py-4" style="max-width: 980px;">

    <x-steps :current="1" />

    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="h4 fw-bold mb-3">{{ __('booking.summary_title') }}</h1>

            <div class="pdj-card mb-3">
                <div class="d-flex gap-3">
                    <img src="{{ $booking->vehicle->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                         class="rounded" width="120" height="84" style="object-fit: cover;" alt="">
                    <div>
                        <div class="fw-bold">{{ $booking->vehicle->fullName() }}</div>
                        <div class="small text-muted">{{ $booking->vehicle->category->name($locale) }}</div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $period->startsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->startsAtHour()) }}
                            <i class="bi bi-arrow-right mx-1"></i>
                            {{ $period->endsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->endsAtHour()) }}
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i>{{ $quote->days }} {{ __('general.days') }}
                        </div>
                    </div>
                </div>

                <a class="small d-inline-block mt-3"
                   href="{{ route('vehicles.show', ['locale' => $locale, 'vehicle' => $booking->vehicle]) }}?{{ http_build_query([
                       'du' => $period->startsAtDate(), 'au' => $period->endsAtDate(),
                       'heure_depart' => $period->startsAtHour(), 'heure_retour' => $period->endsAtHour(),
                   ]) }}">
                    {{ __('booking.change_vehicle') }}
                </a>
            </div>

            {{-- ── Option chauffeur ─────────────────────────────────────────── --}}
            <div class="driver-option mb-3">
                <form action="{{ route('booking.driver', ['locale' => $locale, 'booking' => $booking]) }}" method="POST">
                    @csrf
                    <div class="d-flex align-items-start gap-2">
                        <div class="form-check mt-1">
                            <input class="form-check-input form-check-input-lg" type="checkbox" name="avec_chauffeur" value="1"
                                   id="avec_chauffeur" data-driver-toggle
                                   @checked($booking->with_driver)
                                   @disabled(! $driverAvailable)>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-check-label fw-bold" for="avec_chauffeur">
                                {{ __('driver.badge') }} — {{ __('driver.title') }}
                            </label>

                            @if (! $driverAvailable)
                                <div class="hint text-danger mt-1">{{ __('driver.none_available') }}</div>
                            @else
                                @if (! $quote->legacyDriverPricing || ! $booking->with_driver)
                                    <div class="hint mt-1">
                                        {{ __('driver.hint', [
                                            'price' => number_format($quote->driverDailyPrice, 0, ' ', ' ').' FCFA',
                                        ]) }}
                                    </div>
                                @endif

                                @if ($booking->with_driver)
                                    <div class="blocks mt-2">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        @if ($quote->legacyDriverPricing)
                                            {{ __('booking.line.driver', ['blocks' => $quote->driverBlocks]) }} —
                                        @else
                                            {{ $quote->days }} {{ __('general.days') }} ×
                                            {{ number_format($quote->driverDailyPrice, 0, ' ', ' ') }} FCFA —
                                        @endif
                                        {{ number_format($quote->priceDriver, 0, ' ', ' ') }} FCFA
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if ($driverAvailable)
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-2 d-none">
                            {{ __('booking.update_price') }}
                        </button>
                    @endif
                </form>
            </div>

            @if ($booking->with_driver)
                <div class="alert alert-info d-flex gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span class="small">{{ __('driver.no_license_needed') }}</span>
                </div>
            @endif

            <a class="btn btn-primary btn-lg w-100" href="{{ route('booking.contact', ['locale' => $locale, 'booking' => $booking]) }}">
                {{ __('booking.continue') }} <i class="bi bi-arrow-right ms-1"></i>
            </a>

            <p class="small text-muted text-center mt-2 mb-0">{{ __('general.no_online_payment') }}</p>
        </div>

        <div class="col-lg-5">
            <div class="section-title">{{ __('booking.cost_summary') }}</div>
            <x-price-ticket :quote="$quote" />
        </div>
    </div>
</div>

@endsection
