@extends('layouts.app')

@section('title', __('booking.lookup.title'))

@section('content')
<div class="container py-4" style="max-width: 720px;">
    <h1 class="h3 fw-bold mb-2">{{ __('booking.lookup.title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.lookup.intro') }}</p>

    <form method="POST" action="{{ route('booking.lookup.store', ['locale' => $locale]) }}" class="pdj-card mb-4">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col">
                <label for="reference" class="form-label">{{ __('booking.lookup.reference_label') }}</label>
                <input type="text" class="form-control text-uppercase font-monospace" id="reference" name="reference"
                       value="{{ $reference ?? '' }}" placeholder="PDJ-XXXXXX" maxlength="12" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">{{ __('booking.lookup.submit') }}</button>
            </div>
        </div>
        @error('reference')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
    </form>

    @if ($error)
        <div class="alert alert-warning">{{ $error }}</div>
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
                <dt class="col-sm-4">{{ __('booking.lookup.service') }}</dt>
                <dd class="col-sm-8">{{ __('driver.solo_title') }}</dd>
                <dt class="col-sm-4">{{ __('booking.guest.period') }}</dt>
                <dd class="col-sm-8">
                    {{ $driverOnlyBooking->starts_at->translatedFormat('d/m/Y H:i') }} →
                    {{ $driverOnlyBooking->ends_at->translatedFormat('d/m/Y H:i') }}
                </dd>
                <dt class="col-sm-4">{{ __('booking.lookup.total') }}</dt>
                <dd class="col-sm-8">{{ number_format($driverOnlyBooking->price_total, 0, ' ', ' ') }} FCFA</dd>
            </dl>

            <p class="small text-muted mt-3 mb-0">{{ __('booking.lookup.driver_only_received') }}</p>
        </div>
    @endif
</div>
@endsection