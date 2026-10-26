@extends('layouts.app')

@section('title', __('booking.lookup.title'))

@section('content')
<div class="container py-4" style="max-width: 720px;">
    <h1 class="h3 fw-bold mb-2">{{ __('booking.lookup.title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.lookup.intro') }}</p>

    <form method="POST" action="{{ route('booking.lookup.store', ['locale' => $locale]) }}" class="pdj-card mb-4">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-12 col-sm-4">
                <label for="reference" class="form-label">{{ __('booking.lookup.reference_label') }}</label>
                <input type="text" class="form-control font-monospace text-uppercase" id="reference" name="reference"
                       value="{{ $reference ?? '' }}" placeholder="PDJ-XXXXXX" maxlength="20" autocomplete="off">
                @error('reference')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-sm-5">
                <label for="customer_name" class="form-label">{{ __('booking.lookup.name_label') }}</label>
                <input type="text" class="form-control" id="customer_name" name="customer_name"
                       value="{{ $customerName ?? '' }}" placeholder="{{ __('booking.lookup.name_placeholder') }}"
                       maxlength="100" autocomplete="name">
                @error('customer_name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-primary w-100">{{ __('booking.lookup.submit') }}</button>
            </div>
        </div>
        <p class="form-text mb-0 mt-2">{{ __('booking.lookup.search_hint') }}</p>
    </form>

    @if ($error)
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    {{-- Plusieurs homonymes : on liste les dossiers et on laisse le client
         ouvrir le sien par sa référence, sans afficher le détail de tous. --}}
    @if ($matches->count() > 1)
        <div class="pdj-card">
            <h2 class="h5 fw-bold mb-1">{{ __('booking.lookup.results_title') }}</h2>
            <p class="small text-muted mb-3">{{ __('booking.lookup.results_hint') }}</p>

            <ul class="list-unstyled mb-0">
                @foreach ($matches as $match)
                    <li class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 border-top">
                        <div>
                            <div class="fw-semibold">{{ $match['customer'] ?: '—' }}</div>
                            <div class="small text-muted">{{ $match['service'] }} · {{ $match['period'] }}</div>
                            <div class="small font-monospace">{{ $match['reference'] }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $match['color'] }}">{{ $match['status'] }}</span>
                            <form method="POST" action="{{ route('booking.lookup.store', ['locale' => $locale]) }}">
                                @csrf
                                <input type="hidden" name="reference" value="{{ $match['reference'] }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    {{ __('booking.lookup.details') }}
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($booking)
        <div class="pdj-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="small text-muted">{{ __('booking.guest.status') }}</div>
                    <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                </div>
                <div class="text-end">
                    <div class="small text-muted">{{ __('booking.lookup.reference') }}</div>
                    <div class="fw-bold font-monospace">{{ $booking->reference }}</div>
                </div>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('booking.lookup.customer') }}</dt>
                <dd class="col-sm-8">{{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}</dd>
                <dt class="col-sm-4">{{ __('admin.vehicle') }}</dt>
                <dd class="col-sm-8">{{ $booking->vehicle->fullName() }}</dd>
                <dt class="col-sm-4">{{ __('booking.guest.period') }}</dt>
                <dd class="col-sm-8">
                    {{ $booking->starts_at->translatedFormat('d/m/Y H:i') }} →
                    {{ $booking->ends_at->translatedFormat('d/m/Y H:i') }}
                </dd>
                <dt class="col-sm-4">{{ __('booking.lookup.service') }}</dt>
                <dd class="col-sm-8">
                    {{ $booking->with_driver ? __('admin.service_with_driver') : __('admin.service_car_only') }}
                </dd>
                <dt class="col-sm-4">{{ __('booking.lookup.document_verification') }}</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-{{ $booking->document_verification_status->color() }}">
                        {{ $booking->document_verification_status->label() }}
                    </span>
                </dd>
            </dl>

            @if (in_array($booking->status->value, ['en_attente', 'confirmee'], true))
                <div class="mt-3 small text-muted">
                    <p class="mb-1">{{ __('booking.guest.confirmation_documents') }}</p>
                    <ul class="mb-0">
                        <li>{{ __('document.type.piece_identite') }} — {{ __('booking.original') }}</li>
                        @unless ($booking->with_driver)
                            <li>{{ __('document.type.permis') }} — {{ __('booking.original') }}</li>
                        @endunless
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if ($driverOnlyBooking)
        <div class="pdj-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="small text-muted">{{ __('booking.guest.status') }}</div>
                    <span class="badge bg-{{ $driverOnlyBooking->status->color() }}">
                        {{ $driverOnlyBooking->status->label() }}
                    </span>
                </div>
                <div class="text-end">
                    <div class="small text-muted">{{ __('booking.lookup.reference') }}</div>
                    <div class="fw-bold font-monospace">{{ $driverOnlyBooking->reference }}</div>
                </div>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('booking.lookup.customer') }}</dt>
                <dd class="col-sm-8">{{ $driverOnlyBooking->customer_name }}</dd>
                <dt class="col-sm-4">{{ __('booking.lookup.service') }}</dt>
                <dd class="col-sm-8">{{ __('driver.solo_title') }}</dd>
                <dt class="col-sm-4">{{ __('booking.guest.period') }}</dt>
                <dd class="col-sm-8">
                    {{ $driverOnlyBooking->starts_at->translatedFormat('d/m/Y H:i') }} →
                    {{ $driverOnlyBooking->ends_at->translatedFormat('d/m/Y H:i') }}
                </dd>
                <dt class="col-sm-4">{{ __('booking.lookup.total') }}</dt>
                <dd class="col-sm-8">{{ app(\App\Services\SettingsService::class)->money($driverOnlyBooking->price_total) }}</dd>
            </dl>

            <p class="small text-muted mt-3 mb-0">{{ __('booking.lookup.driver_only_received') }}</p>
        </div>
    @endif
</div>
@endsection
