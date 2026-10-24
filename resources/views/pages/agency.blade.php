@extends('pages.layout')

@section('title', __('page.agency_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.agency_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.agency_intro') }}</p>

    <div class="pdj-card mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="section-title">{{ __('page.agency_address') }}</div>
                <p class="mb-3"><i class="bi bi-geo-alt me-2"></i>{{ $address }}</p>
                <p class="mb-3">
                    <i class="bi bi-telephone me-2"></i>
                    <a href="tel:{{ preg_replace('/\D/', '', $phone) }}">{{ $phone }}</a>
                </p>
                <div class="section-title">{{ __('page.agency_hours') }}</div>
                <p class="mb-0">{{ __('general.opening_hours') }}<br>{{ $open }} – {{ $close }}</p>
            </div>
            <div class="col-md-6">
                <div class="section-title">{{ __('page.agency_get_there') }}</div>
                <a class="btn btn-outline-primary w-100 mb-2"
                   href="https://www.google.com/maps/search/?api=1&query={{ urlencode($address) }}" target="_blank" rel="noopener">
                    <i class="bi bi-map me-1"></i>{{ __('booking.open_maps') }}
                </a>
                <a class="btn btn-accent w-100"
                   href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode(__('general.whatsapp_help')) }}" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i>{{ __('general.contact_whatsapp') }}
                </a>
            </div>
        </div>
    </div>

    <div class="section-title">{{ __('page.agency_fleet') }}</div>
    <div class="row g-3">
        @foreach ($categories as $category)
            <div class="col-sm-6 col-lg-4">
                <div class="pdj-card h-100">
                    <div class="fw-bold">{{ $category->name($locale) }}</div>
                    <div class="small text-muted">
                        {{ trans_choice('{0} :count véhicule|{1} :count véhicule|[2,*] :count véhicules', $category->vehicles_count, ['count' => $category->vehicles_count]) }}
                    </div>
                    <div class="fw-semibold mt-2" style="color: var(--pdj-green);">
                        {{ number_format($category->daily_price, 0, ' ', ' ') }} FCFA / {{ __('general.day_short') }}
                    </div>
                    <a class="small d-inline-block mt-2" href="{{ route('vehicles.category', ['locale' => $locale, 'category' => $category->slug]) }}">
                        {{ __('general.see_vehicle') }} <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
