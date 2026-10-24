@extends('layouts.app')

@section('title', __('booking.step.review'))

@section('content')

<div class="container py-4" style="max-width: 760px;">

    <x-steps :current="5" />

    <h1 class="h4 fw-bold mb-1">{{ __('booking.review_title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.review_intro') }}</p>

    @if ($missing !== [])
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="small">
                <strong>{{ __('booking.missing_documents') }}</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($missing as $type)
                        <li>{{ \App\Enums\DocumentType::from($type)->label() }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <a class="btn btn-primary mb-4"
           href="{{ route('booking.documents', ['locale' => $locale, 'booking' => $booking]) }}">
            {{ __('booking.back_to_documents') }}
        </a>
    @endif

    <div class="pdj-card mb-3">
        <ul class="list-unstyled mb-0 small">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $booking->vehicle->fullName() }}</li>
            <li class="mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                {{ $period->startsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->startsAtHour()) }}
                <i class="bi bi-arrow-right mx-1"></i>
                {{ $period->endsAt->translatedFormat('D d M') }} {{ sprintf('%02dh00', $period->endsAtHour()) }}
            </li>
            <li class="mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                {{ app(\App\Services\SettingsService::class)->money($quote->priceTotal) }} {{ __('booking.to_pay') }}
            </li>

            @if ($booking->with_driver)
                <li class="mb-2">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    @if ($quote->legacyDriverPricing)
                        {{ __('booking.line.driver', ['blocks' => $quote->driverBlocks]) }} —
                        {{ app(\App\Services\SettingsService::class)->money($quote->priceDriver) }}
                    @else
                        {{ __('driver.badge') }} — {{ $quote->days }} {{ __('general.days') }} ×
                        {{ app(\App\Services\SettingsService::class)->money($quote->driverDailyPrice) }}
                    @endif
                </li>
            @endif

            @if ($booking->user)
                <li class="mb-2">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    {{ $booking->user->displayName() }} · {{ $booking->user->phone }}
                    @if ($booking->hasPhoneVerified())
                        <i class="bi bi-patch-check-fill text-success" data-bs-toggle="tooltip"
                           title="{{ __('booking.phone_verified') }}"></i>
                    @endif
                </li>
            @endif

            @foreach ($booking->documents as $document)
                <li class="mb-2">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    {{ $document->type->label() }}
                    <span class="badge bg-{{ $document->status->color() }} ms-1">{{ $document->status->label() }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <x-price-ticket :quote="$quote" class="mb-3" />

    @if ($missing === [])
        <form action="{{ route('booking.store', ['locale' => $locale, 'booking' => $booking]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg w-100">
                {{ __('booking.confirm_cta') }}
            </button>
        </form>
    @else
        <button class="btn btn-primary btn-lg w-100" disabled>{{ __('booking.confirm_cta') }}</button>
    @endif

    <p class="small text-muted text-center mt-3 mb-0">{{ __('booking.confirm_note') }}</p>
</div>

@endsection
