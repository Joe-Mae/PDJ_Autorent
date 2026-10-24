@extends('layouts.app')

@section('title', __('booking.guest.title'))
@section('description', __('booking.guest.intro'))

@section('content')

<div class="container py-4" style="max-width: 860px;">
    <a class="small d-inline-block mb-3"
       href="{{ route('vehicles.show', ['locale' => app()->getLocale(), 'vehicle' => $vehicle]) }}">
        <i class="bi bi-arrow-left me-1"></i>{{ __('booking.change_vehicle') }}
    </a>

    <h1 class="h3 fw-bold mb-2">{{ __('booking.guest.title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.guest.intro') }}</p>

    <div class="pdj-card mb-3">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $vehicle->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                 class="rounded" width="120" height="84" style="object-fit: cover;" alt="">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $vehicle->fullName() }}</h2>
                <div class="small text-muted">{{ $vehicle->category->name(app()->getLocale()) }}</div>
                @if ($withDriver)
                    <div class="small text-muted">{{ __('booking.guest.driver_request_title') }}</div>
                @endif
            </div>
        </div>
    </div>

    <form action="{{ route('booking.guest.store', ['locale' => app()->getLocale(), 'vehicle' => $vehicle]) }}"
          method="POST"
          class="pdj-card"
          data-guest-booking-form
          data-quote-url="{{ route('booking.guest.quote', ['locale' => app()->getLocale(), 'vehicle' => $vehicle]) }}"
          data-quote-error="{{ __('booking.guest.quote_error') }}">
        @csrf
        <div class="driver-option mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="avec_chauffeur" value="1"
                       id="avec_chauffeur" data-guest-driver-toggle @checked($withDriver)>
                <label class="form-check-label fw-bold" for="avec_chauffeur">
                    Avec un chauffeur ?
                </label>
            </div>
            <div class="mt-1 @if (! $withDriver) d-none @endif" data-guest-driver-info>
                <p class="hint mb-0">Le chauffeur est inclus dans cette demande et son tarif s'ajoute au prix de la location.</p>
                <p class="small text-muted mb-0">
                    {{ app(\App\Services\SettingsService::class)->money($quote->driverDailyPrice) }} par jour avec la voiture.
                </p>
            </div>
        </div>

        <h2 class="h5 fw-bold mb-3">{{ __('booking.guest.period') }}</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="du" class="form-label">{{ __('booking.guest.start_date') }}</label>
                <input type="date" class="form-control" id="du" name="du"
                       value="{{ old('du', $period->startsAtDate()) }}" min="{{ today()->toDateString() }}" required>
            </div>
            <div class="col-md-6">
                <label for="au" class="form-label">{{ __('booking.guest.end_date') }}</label>
                <input type="date" class="form-control" id="au" name="au"
                       value="{{ old('au', $period->endsAtDate()) }}" min="{{ today()->toDateString() }}" required>
            </div>
            <div class="col-md-6">
                <label for="heure_depart" class="form-label">{{ __('booking.guest.pickup_hour') }}</label>
                <input type="number" class="form-control" id="heure_depart" name="heure_depart"
                       min="{{ $period->openHour }}" max="{{ $period->closeHour }}"
                       value="{{ old('heure_depart', $period->startsAtHour()) }}" required>
            </div>
            <div class="col-md-6">
                <label for="heure_retour" class="form-label">{{ __('booking.guest.return_hour') }}</label>
                <input type="number" class="form-control" id="heure_retour" name="heure_retour"
                       min="{{ $period->openHour }}" max="{{ $period->closeHour }}"
                       value="{{ old('heure_retour', $period->endsAtHour()) }}" required>
            </div>
        </div>

        <div class="border rounded p-3 mt-4" aria-live="polite">
            <div class="small text-muted">{{ __('booking.guest.daily_rate') }}</div>
            <div class="fw-semibold">
                <span data-guest-daily-price>{{ number_format($quote->dailyPrice, 0, ' ', ' ') }}</span> {{ app(\App\Services\SettingsService::class)->currency() }}
            </div>
            <div class="small text-muted mt-2">
                {{ __('booking.guest.days') }}:
                <span data-guest-days>{{ $quote->days }}</span>
            </div>
            <div class="d-flex justify-content-between small mt-3">
                <span>{{ __('quote.line.rental', ['days' => $quote->days]) }}</span>
                <span class="text-nowrap">
                    <span data-guest-rental-total>{{ number_format($quote->priceRental, 0, ' ', ' ') }}</span> {{ app(\App\Services\SettingsService::class)->currency() }}
                </span>
            </div>
            <div class="d-flex justify-content-between small mt-1 @if (! $withDriver) d-none @endif"
                 data-guest-driver-line>
                <span>{{ __('quote.line.driver', ['days' => $quote->days]) }}</span>
                <span class="text-nowrap">
                    <span data-guest-driver-total>{{ number_format($quote->priceDriver, 0, ' ', ' ') }}</span> {{ app(\App\Services\SettingsService::class)->currency() }}
                </span>
            </div>
            <div class="fw-bold mt-2">
                {{ __('booking.guest.estimated_total') }}:
                <span data-guest-total>{{ number_format($quote->priceTotal, 0, ' ', ' ') }}</span> {{ app(\App\Services\SettingsService::class)->currency() }}
            </div>
            <div class="small text-muted mt-1" data-guest-quote-error hidden></div>
        </div>

        <h2 class="h5 fw-bold mt-4 mb-3">{{ __('booking.guest.contact') }}</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="customer_name" class="form-label">{{ __('booking.guest.name') }}</label>
                <input type="text" class="form-control" id="customer_name" name="customer_name"
                       value="{{ old('customer_name') }}" maxlength="160" autocomplete="name" required>
            </div>
            <div class="col-md-6">
                <label for="customer_phone" class="form-label">{{ __('booking.field_phone') }}</label>
                <input type="tel" class="form-control" id="customer_phone" name="customer_phone"
                       value="{{ old('customer_phone') }}" maxlength="20" autocomplete="tel" required>
            </div>
            <div class="col-12">
                <label for="customer_email" class="form-label">{{ __('booking.field_email') }}</label>
                <input type="email" class="form-control" id="customer_email" name="customer_email"
                       value="{{ old('customer_email') }}" maxlength="255" autocomplete="email" required>
            </div>
            <div class="col-12">
                <label for="customer_comment" class="form-label">{{ __('booking.guest.comment') }}</label>
                <textarea class="form-control" id="customer_comment" name="customer_comment"
                          rows="3" maxlength="1000">{{ old('customer_comment') }}</textarea>
            </div>
        </div>

        <p class="small text-muted mt-3 mb-3">{{ __('booking.guest.pending_notice') }}</p>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('booking.guest.submit') }}</button>
    </form>
</div>

@endsection
