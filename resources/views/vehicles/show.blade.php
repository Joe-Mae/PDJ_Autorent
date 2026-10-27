@extends('layouts.app')

@section('title', $vehicle->fullName())
@section('description', $vehicle->category->description($locale) ?? $vehicle->displayName())

@section('content')

@php
    $photos = $vehicle->galleryPhotos();
    $description = $vehicle->description($locale);
@endphp

<div class="container py-4">
    <nav aria-label="breadcrumb" class="small text-muted mb-3">
        <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('nav.home') }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => $vehicle->category->slug]) }}">
            {{ $vehicle->category->name($locale) }}
        </a>
        <span class="mx-1">/</span>
        <span>{{ $vehicle->displayName() }}</span>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <div data-gallery data-gallery-interval="5000">
                <div class="gallery-main">
                    <img data-gallery-main
                         src="{{ $photos->first()?->getUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                         alt="{{ $vehicle->fullName() }}"
                         width="800" height="500">
                </div>
                @if ($photos->count() > 1)
                    <div class="gallery-thumbs">
                        @foreach ($photos as $index => $media)
                            <button type="button"
                                    class="gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                    data-gallery-thumb="{{ $media->getUrl() }}"
                                    aria-label="{{ __('vehicle.gallery_photo', ['number' => $index + 1, 'total' => $photos->count()]) }}">
                                <img src="{{ $media->getUrl() }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <span class="badge-assurance"><i class="bi bi-shield-check me-1"></i>{{ __('general.reassurance.insurance') }}</span>
            <h1 class="h3 fw-bold mt-2 mb-1">{{ $vehicle->fullName() }}</h1>
            <p class="text-muted mb-3">{{ $vehicle->category->name($locale) }}</p>

            <div class="spec-grid mb-3">
                <div class="spec"><i class="bi bi-gear"></i>{{ $vehicle->transmissionLabel() }}</div>
                <div class="spec"><i class="bi bi-fuel-pump"></i>{{ $vehicle->fuelLabel() }}</div>
                <div class="spec"><i class="bi bi-people"></i>{{ $vehicle->seats }} {{ __('vehicle.seats') }}</div>
                <div class="spec"><i class="bi bi-bag"></i>{{ $vehicle->bags }} {{ __('vehicle.bags') }}</div>
                @if ($vehicle->air_conditioning)
                    <div class="spec"><i class="bi bi-snow"></i>{{ __('vehicle.air_con') }}</div>
                @endif
                <div class="spec"><i class="bi bi-calendar3"></i>{{ $vehicle->year }}</div>
            </div>

            @if ($quote)
                <x-price-ticket :quote="$quote" />
            @endif
        </div>
    </div>

    <div class="row g-4 mt-1">
        {{-- ── Réservation : un seul bouton, un seul but ───────────────────── --}}
        <div class="col-lg-7">
            @if (filled($description))
                <div class="pdj-card mb-4">
                    <div class="section-title">{{ __('vehicle.about') }}</div>
                    <p class="mb-0" style="white-space: pre-line;">{{ $description }}</p>
                </div>
            @endif

            <div class="pdj-card mb-4">
                <div class="section-title">{{ __('vehicle.conditions') }}</div>
                <ul class="condition-list mb-0 ps-3">
                    <li>{{ __('vehicle.cond_age') }}</li>
                    <li>{{ __('vehicle.cond_license') }}</li>
                    <li>{{ __('vehicle.cond_km') }}</li>
                    <li>{{ __('vehicle.cond_fuel') }}</li>
                    <li>{{ __('vehicle.cond_speed') }}</li>
                    <li>{{ __('vehicle.cond_insurance', ['amount' => app(\App\Services\SettingsService::class)->money(app(\App\Services\SettingsService::class)->int('booking.insurance_deductible'))]) }}</li>
                    <li>{{ __('vehicle.cond_cancellation') }}</li>
                </ul>
            </div>

            @if ($reviews->isNotEmpty())
                <div class="pdj-card">
                    <div class="section-title">{{ __('general.reviews_title') }}</div>
                    @foreach ($reviews as $review)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="rating-stars">{{ str_repeat('★', $review->rating) }}</div>
                            <p class="small mb-1">{{ $review->comment }}</p>
                            <div class="small text-muted">
                                <strong>{{ $review->author_name }}</strong>
                                @if ($review->author_city) — {{ $review->author_city }} @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Colonne de réservation ─────────────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="d-none d-lg-block">
                @include('vehicles.partials.book-box')
            </div>

            @if ($similar->isNotEmpty())
                <div class="mt-4">
                    <div class="section-title">{{ __('vehicle.similar') }}</div>
                    @foreach ($similar as $item)
                        <a class="d-flex align-items-center gap-3 text-decoration-none text-reset mb-2"
                           href="{{ route('vehicles.show', ['locale' => $locale, 'vehicle' => $item]) }}?{{ http_build_query(request()->query()) }}">
                            <img src="{{ $item->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                                 class="rounded" width="72" height="52" style="object-fit: cover;" alt="" loading="lazy">
                            <span class="small">
                                <strong>{{ $item->displayName() }}</strong><br>
                                <span class="text-muted">{{ app(\App\Services\SettingsService::class)->money($item->category->daily_price) }} / {{ __('general.day_short') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Sur mobile, le bouton de réservation reste collé en bas : c'est là que le
     pouce est, et c'est la seule action possible sur cette page. --}}
@include('vehicles.partials.book-box', ['sticky' => true])

@endsection
