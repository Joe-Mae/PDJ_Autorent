@extends('layouts.app')

@section('title', __('general.app_name'))
@section('description', __('general.meta_description'))

@section('content')

{{-- ── Bandeau d'accueil ───────────────────────────────────────────────────── --}}
@php($hero = $banners->first())
<section class="pdj-hero home-hero" data-hero-carousel>
    <div class="home-hero__slides" aria-hidden="true">
        @foreach ($heroImages as $image)
            <div class="home-hero__slide {{ $loop->first ? 'is-active' : '' }}"
                 data-hero-slide
                 aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                <img src="{{ $image }}" alt="" @if (! $loop->first) loading="lazy" @endif>
            </div>
        @endforeach
    </div>

    <div class="container home-hero__content">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 col-xl-7">
                <p class="home-hero__eyebrow">{{ $heroEyebrow }}</p>
                <h1 class="display-4 fw-bold mb-3">
                    {{ $hero?->title($locale) ?? __('general.hero_title') }}
                </h1>
                <p class="lead mb-4">
                    {{ $hero?->subtitle($locale) ?? __('general.hero_subtitle') }}
                </p>
                <div class="home-hero__actions">
                    <a class="btn btn-primary btn-lg" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
                        {{ $navLabels['fleet'] }} <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="{{ route('page.driver', ['locale' => $locale]) }}">
                        {{ $navLabels['driver'] }}
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-xl-5 d-none d-lg-block">
                <div class="pdj-hero__proof">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <i class="bi bi-shield-check fs-3"></i>
                        <div>
                            <div class="fw-bold">{{ __('general.reassurance.insurance') }}</div>
                            <div class="small text-muted">{{ __('vehicle.insurance_subtitle', ['amount' => app(\App\Services\SettingsService::class)->money(app(\App\Services\SettingsService::class)->int('booking.insurance_deductible'))]) }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-wallet2 fs-3"></i>
                        <div>
                            <div class="fw-bold">{{ __('general.no_online_payment') }}</div>
                            <div class="small text-muted">{{ __('general.no_online_payment_hint') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($heroImages->count() > 1)
        <div class="home-hero__controls" aria-label="{{ __('general.hero_carousel_controls') }}">
            <button type="button" data-hero-previous aria-label="{{ __('general.hero_previous_slide') }}">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div class="home-hero__dots">
                @foreach ($heroImages as $index => $image)
                    <button type="button"
                            data-hero-dot="{{ $index }}"
                            aria-label="{{ __('general.hero_goto_slide', ['number' => $index + 1]) }}"
                            aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
            <button type="button" data-hero-next aria-label="{{ __('general.hero_next_slide') }}">
                <i class="bi bi-arrow-right"></i>
            </button>
            <button type="button"
                    data-hero-toggle
                    data-pause-label="{{ __('general.hero_pause_slideshow') }}"
                    data-play-label="{{ __('general.hero_play_slideshow') }}"
                    aria-label="{{ __('general.hero_pause_slideshow') }}"
                    aria-pressed="false">
                <i class="bi bi-pause-fill"></i>
            </button>
        </div>
    @endif
</section>

{{-- ── Recherche ──────────────────────────────────────────────────────────── --}}
<section class="home-search">
    <x-search-panel class="search-panel--wide" />

    <div class="reassurance-strip mt-3 justify-content-center">
        <span><i class="bi bi-shield-check"></i>{{ __('general.reassurance.insurance') }}</span>
        <span><i class="bi bi-arrow-counterclockwise"></i>{{ __('general.reassurance.free_cancellation') }}</span>
        <span><i class="bi bi-speedometer2"></i>{{ __('general.reassurance.unlimited_km') }}</span>
        <span><i class="bi bi-headset"></i>{{ __('general.reassurance.support') }}</span>
    </div>
</section>

{{-- ── Comment ça marche ──────────────────────────────────────────────────── --}}
<section class="container py-5">
    <h2 class="section-title mb-4" data-reveal>{{ __('general.how_it_works') }}</h2>
    <div class="row g-4">
        @foreach ([
            ['search', '1', 'calendar-check'],
            ['reserve', '2', 'pencil-square'],
            ['pickup', '3', 'cash-coin'],
        ] as $index => [$key, $number, $icon])
            <div class="col-md-4">
                <div class="h-100" data-reveal data-delay="{{ $index + 1 }}">
                    <div class="feature-icon"><i class="bi bi-{{ $icon }}"></i></div>
                    <div class="small fw-bold text-muted mb-1">{{ __('general.step_number', ['number' => $number]) }}</div>
                    <h3 class="h6 fw-bold">{{ __('general.step_'.$key.'_title') }}</h3>
                    <p class="small text-muted mb-0">{{ __('general.step_'.$key.'_text') }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ── Catégories ─────────────────────────────────────────────────────────── --}}
@if ($categories->isNotEmpty())
    <section class="container pb-5">
        <h2 class="section-title mb-4" data-reveal>{{ __('general.categories_title') }}</h2>
        <div class="row g-3">
            @foreach ($categories as $category)
                <div class="col-6 col-md-4 col-lg-2" data-reveal>
                    <a class="d-block text-decoration-none text-reset pdj-card pdj-card--category h-100"
                       href="{{ route('vehicles.category', ['locale' => $locale, 'category' => $category->slug]) }}">
                        <div class="fw-bold">{{ $category->name($locale) }}</div>
                        <div class="small text-muted">
                            {{ trans_choice('{0} :count voiture|{1} :count voiture|[2,*] :count voitures', $category->vehicles_count, ['count' => $category->vehicles_count]) }}
                        </div>
                        <div class="small fw-semibold mt-2" style="color: var(--pdj-green);">
                            {{ app(\App\Services\SettingsService::class)->money($category->daily_price) }} / {{ __('general.day_short') }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- ── Véhicules en vedette ────────────────────────────────────────────────── --}}
@if ($featured->isNotEmpty())
    <section class="container pb-5">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="section-title mb-0" data-reveal>{{ __('general.featured_title') }}</h2>
            <a href="{{ route('vehicles.index', ['locale' => $locale]) }}" class="small">
                {{ __('general.see_all') }} <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-3">
            @foreach ($featured as $vehicle)
                <div class="col-md-6 col-lg-4" data-reveal>
                    <a class="d-block text-decoration-none text-reset pdj-card h-100"
                       href="{{ route('vehicles.show', ['locale' => $locale, 'vehicle' => $vehicle]) }}">
                        <div class="rounded overflow-hidden mb-3" style="aspect-ratio: 16/10; background: var(--pdj-cream);">
                            <img src="{{ $vehicle->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;"
                                 alt="{{ $vehicle->fullName() }}"
                                 loading="lazy">
                        </div>
                        <div class="fw-bold">{{ $vehicle->fullName() }}</div>
                        <div class="small text-muted">
                            {{ $vehicle->transmissionLabel() }} · {{ $vehicle->fuelLabel() }} · {{ $vehicle->seats }} {{ __('vehicle.seats') }}
                        </div>
                        <div class="fw-bold mt-2" style="color: var(--pdj-green);">
                            {{ app(\App\Services\SettingsService::class)->money($vehicle->category->daily_price) }} / {{ __('general.day_short') }}
                        </div>
                        <div class="small text-muted">
                            {{ __('general.deposit', ['price' => app(\App\Services\SettingsService::class)->money($vehicle->category->deposit_amount)]) }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- ── Avec chauffeur ──────────────────────────────────────────────────────── --}}
<section class="pdj-band pdj-band--flagged">
    <div class="container">
        <div class="pdj-card pdj-card--driver" data-reveal>
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge-assurance">{{ __('driver.badge') }}</span>
                <h2 class="h4 fw-bold mt-2 mb-2">{{ __('driver.title') }}</h2>
                <p class="mb-3">{{ __('driver.intro') }}</p>
                <ul class="list-unstyled small mb-3">
                    <li class="mb-1"><i class="bi bi-check2 me-1" style="color: var(--pdj-green);"></i>{{ __('driver.point1', ['price' => app(\App\Services\SettingsService::class)->money(app(\App\Services\SettingsService::class)->int('driver.block_price'))]) }}</li>
                    <li class="mb-1"><i class="bi bi-check2 me-1" style="color: var(--pdj-green);"></i>{{ __('driver.point2') }}</li>
                    <li class="mb-0"><i class="bi bi-check2 me-1" style="color: var(--pdj-green);"></i>{{ __('driver.point3') }}</li>
                </ul>
                <a class="btn btn-primary" href="{{ route('page.driver', ['locale' => $locale]) }}">
                    {{ __('driver.cta') }}
                </a>
            </div>
            <div class="col-lg-5 text-center">
                <div class="display-4 fw-bold" style="color: var(--pdj-green);">
                    {{ number_format(app(\App\Services\SettingsService::class)->int('driver.block_price'), 0, ' ', ' ') }}
                </div>
                <div class="text-muted">{{ __('driver.price_unit', ['currency' => app(\App\Services\SettingsService::class)->currency()]) }}</div>
            </div>
        </div>
    </div>
</section>

{{-- ── Avis ────────────────────────────────────────────────────────────────── --}}
@if ($reviews->isNotEmpty())
    <section class="pdj-band">
        <div class="container">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="section-title mb-0" data-reveal>{{ __('general.reviews_title') }}</h2>
            @if ($reviewCount > 0)
                <span class="small text-muted">
                    <span class="rating-stars">★★★★★</span>
                    {{ number_format($averageRating, 1) }} · {{ $reviewCount }} {{ __('general.reviews_count') }}
                </span>
            @endif
        </div>

        <div class="row g-3">
            @foreach ($reviews as $index => $review)
                <div class="col-md-4" data-reveal data-delay="{{ min($index + 1, 8) }}">
                    <div class="pdj-card h-100">
                        <div class="rating-stars mb-2">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                        <p class="small mb-3">{{ $review->comment }}</p>
                        <div class="small text-muted mt-auto">
                            <strong>{{ $review->author_name }}</strong>
                            @if ($review->author_city) — {{ $review->author_city }} @endif
                        </div>
                        <div class="small text-muted">{{ __('general.verified_booking') }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="small text-muted mt-3 mb-0">
            <i class="bi bi-patch-check me-1"></i>{{ __('general.reviews_note') }}
        </p>
        </div>
    </section>
@endif

{{-- ── FAQ courte ──────────────────────────────────────────────────────────── --}}
@if ($faqs->isNotEmpty())
    <section class="container pt-4 pb-5">
        <h2 class="section-title mb-3" data-reveal>{{ __('general.faq_title') }}</h2>
        <div class="accordion" id="faqHome">
            @foreach ($faqs as $index => $faq)
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq{{ $index }}" aria-expanded="false">
                            {{ $faq->{'question_'.$locale} ?: $faq->question_fr }}
                        </button>
                    </h3>
                    <div id="faq{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#faqHome">
                        <div class="accordion-body small">
                            {!! nl2br(e($faq->{'answer_'.$locale} ?: $faq->answer_fr)) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <a class="btn btn-link px-0 mt-2" href="{{ route('page.faq', ['locale' => $locale]) }}">
            {{ __('general.all_faqs') }} <i class="bi bi-arrow-right"></i>
        </a>
    </section>
@endif

@endsection
