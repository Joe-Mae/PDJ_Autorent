@extends('layouts.app')

@section('title', __('booking.confirmation_title'))
@section('description', __('booking.confirmation_meta'))

@section('content')

<div class="container py-5" style="max-width: 720px;">

    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-grid" style="width: 64px; height: 64px; border-radius: 50%; background: var(--pdj-green-light); place-items: center;">
            <i class="bi bi-check-lg fs-2" style="color: var(--pdj-green);"></i>
        </div>
        <h1 class="h3 fw-bold mb-2">{{ __('booking.confirmation_title') }}</h1>
        <p class="text-muted mb-3">{{ __('booking.confirmation_intro') }}</p>
        <div class="d-inline-block px-4 py-2 rounded" style="background: var(--pdj-cream); border: 1px solid var(--pdj-line);">
            <span class="small text-muted">{{ __('booking.reference') }}</span>
            <span class="fw-bold ms-2" style="font-size: 1.3rem; letter-spacing: .08em;">{{ $booking->reference }}</span>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
        <a class="btn btn-primary" href="{{ route('booking.receipt', ['locale' => $locale, 'booking' => $booking]) }}">
            <i class="bi bi-file-earmark-pdf me-1"></i>{{ __('booking.download_receipt') }}
        </a>
        <a class="btn btn-outline-primary" href="{{ route('account.bookings.show', ['locale' => $locale, 'booking' => $booking]) }}">
            {{ __('nav.my_account') }}
        </a>
    </div>

    <div class="pdj-card mb-3">
        <div class="section-title">{{ __('booking.what_happens_next') }}</div>
        <p class="small mb-0">{{ __('booking.next_steps') }}</p>
    </div>

    <x-price-ticket :quote="$quote" class="mb-3" />

    <div class="pdj-card mb-3">
        <div class="section-title">{{ __('booking.bring_with_you') }}</div>
        <ul class="list-unstyled mb-0 small">
            <li class="mb-2"><i class="bi bi-person-vcard me-2"></i>{{ __('document.type.piece_identite') }} {{ __('booking.original') }}</li>
            @unless ($booking->with_driver)
                <li class="mb-2"><i class="bi bi-card-text me-2"></i>{{ __('document.type.permis') }} {{ __('booking.original') }}</li>
            @endunless
            <li class="mb-0">
                <i class="bi bi-cash-coin me-2"></i>
                {{ number_format($booking->amountToCarry(), 0, ' ', ' ') }} F CFA
                <span class="text-muted">({{ __('booking.amount_breakdown') }})</span>
            </li>
        </ul>
    </div>

    <div class="pdj-card">
        <div class="section-title">{{ __('booking.agency') }}</div>
        <p class="small mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $address }}</p>
        <p class="small mb-3"><i class="bi bi-telephone me-2"></i>{{ $phone }}</p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-primary"
               href="https://www.google.com/maps/search/?api=1&query={{ urlencode($address) }}" target="_blank" rel="noopener">
                {{ __('booking.open_maps') }}
            </a>
            <a class="btn btn-sm btn-accent" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-1"></i>WhatsApp
            </a>
        </div>
    </div>
</div>

@endsection
