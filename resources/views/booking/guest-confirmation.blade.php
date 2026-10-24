@extends('layouts.app')

@section('title', __('booking.guest.confirmation_title'))
@section('description', __('booking.guest.confirmation_intro'))

@section('content')

<div class="container py-5" style="max-width: 720px;">
    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-grid" style="width: 64px; height: 64px; border-radius: 50%; background: var(--pdj-yellow-soft); place-items: center;">
            <i class="bi bi-clock-history fs-2" style="color: var(--pdj-green-dark);"></i>
        </div>
        <h1 class="h3 fw-bold mb-2">{{ __('booking.guest.confirmation_title') }}</h1>
        <p class="text-muted mb-3">{{ __('booking.guest.confirmation_intro') }}</p>
        <div class="d-inline-block px-4 py-2 rounded" style="background: var(--pdj-cream); border: 1px solid var(--pdj-line);">
            <span class="small text-muted">{{ __('booking.reference') }}</span>
            <span class="fw-bold ms-2" style="font-size: 1.3rem; letter-spacing: .08em;">{{ $booking->reference }}</span>
        </div>
    </div>

    <div class="pdj-card">
        <dl class="row mb-0">
            <dt class="col-sm-5">{{ __('booking.guest.vehicle') }}</dt>
            <dd class="col-sm-7">{{ $booking->vehicle->fullName() }}</dd>

            <dt class="col-sm-5">{{ __('booking.guest.period') }}</dt>
            <dd class="col-sm-7">
                {{ $booking->starts_at->translatedFormat('d/m/Y H:i') }}
                <i class="bi bi-arrow-right mx-1"></i>
                {{ $booking->ends_at->translatedFormat('d/m/Y H:i') }}
            </dd>

            <dt class="col-sm-5">{{ __('quote.line.rental', ['days' => $booking->days]) }}</dt>
            <dd class="col-sm-7">{{ number_format($booking->price_rental, 0, ' ', ' ') }} F CFA</dd>

            @if ($booking->with_driver)
                <dt class="col-sm-5">{{ __('quote.line.driver', ['days' => $booking->days]) }}</dt>
                <dd class="col-sm-7">{{ number_format($booking->price_driver, 0, ' ', ' ') }} F CFA</dd>
            @endif

            <dt class="col-sm-5">{{ __('booking.guest.estimated_total') }}</dt>
            <dd class="col-sm-7 fw-bold">{{ number_format($booking->price_total, 0, ' ', ' ') }} F CFA</dd>

            <dt class="col-sm-5">{{ __('booking.guest.status') }}</dt>
            <dd class="col-sm-7">
                <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
            </dd>
        </dl>
    </div>

    @if ($booking->status->canStillUploadDocuments())
        <div class="pdj-card mt-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div class="small text-muted">
                <i class="bi bi-person-vcard me-1"></i>{{ __('booking.guest.confirmation_documents') }}
                <ul class="mt-2 mb-0">
                    <li>{{ __('document.type.piece_identite') }} — {{ __('booking.original') }}</li>
                    @unless ($booking->with_driver)
                        <li>{{ __('document.type.permis') }} — {{ __('booking.original') }}</li>
                    @endunless
                </ul>
            </div>
            <a class="btn btn-outline-primary"
               href="{{ route('booking.guest.documents', ['locale' => $locale, 'booking' => $booking, 'token' => $token]) }}">
                {{ __('booking.guest.documents_cta') }}
            </a>
        </div>
    @endif

    <p class="small text-center text-muted mt-3">
        @if ($booking->status === \App\Enums\BookingStatus::EnAttente)
            {{ __('booking.guest.confirmation_pending') }}
        @elseif ($booking->status === \App\Enums\BookingStatus::Confirmee)
            {{ __('booking.guest.confirmation_approved') }}
        @elseif ($booking->status === \App\Enums\BookingStatus::Refusee)
            {{ __('booking.guest.confirmation_refused') }}
        @else
            {{ __('booking.guest.confirmation_updated', ['status' => $booking->status->label()]) }}
        @endif
    </p>
</div>

@endsection
