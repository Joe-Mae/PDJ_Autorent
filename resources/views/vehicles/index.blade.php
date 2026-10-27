@extends('layouts.app')

@section('title', $category?->name($locale) ?? __('nav.fleet'))
@section('description', $category?->description($locale) ?? __('general.meta_description'))

@section('content')

<section class="garage-search">
    <x-search-panel
        class="search-panel--wide"
        :action="request()->routeIs('vehicles.category') && $category
            ? route('vehicles.category', ['locale' => $locale, 'category' => $category->slug])
            : route('vehicles.index', ['locale' => $locale])"
        :period="$period"
        :with-driver="(bool) ($filters['with_driver'] ?? false)" />
</section>

<div class="container py-3">
    {{-- Le contrôleur fournit toujours une période : sans dates, c'est le
         créneau par défaut (demain, 3 jours). La flotte affichée est donc
         toujours celle qui est réellement libre, et la période est écrite
         en clair juste au-dessus. --}}
    <div class="row g-4 mt-1">

            {{-- ── Filtres ───────────────────────────────────────────────────── --}}
            <div class="col-lg-3">
                <div class="d-lg-none mb-3">
                    <button class="btn btn-outline-primary w-100" type="button"
                            data-filters-toggle aria-expanded="false" aria-controls="filtersPanel">
                        <i class="bi bi-sliders me-1"></i>{{ __('nav.filters') }}
                    </button>
                </div>

                <div id="filtersPanel" class="d-none d-lg-block filters" data-filters-panel>
                    <form action="{{ route('vehicles.index', ['locale' => $locale]) }}" method="GET"
                          data-filters-form
                          data-filters-url="{{ route('vehicles.filter', ['locale' => $locale]) }}">
                        {{-- Les dates et l'option chauffeur sont hors du formulaire de filtre :
                             elles ont déjà été envoyées, on les conserve. --}}
                        <input type="hidden" name="du" value="{{ $period->startsAtDate() }}">
                        <input type="hidden" name="au" value="{{ $period->endsAtDate() }}">
                        <input type="hidden" name="heure_depart" value="{{ $period->startsAtHour() }}">
                        <input type="hidden" name="heure_retour" value="{{ $period->endsAtHour() }}">
                        @if ($filters['with_driver'] ?? false)
                            <input type="hidden" name="avec_chauffeur" value="1">
                        @endif

                        <div class="pdj-card">
                            <div class="section-title">{{ __('nav.filters') }}</div>

                            <div class="mb-3">
                                <label class="form-label" for="prix_min">{{ __('nav.price') }}</label>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span data-output="prix_min">{{ number_format($filters['price_min'] ?? $priceBounds['min'], 0, ' ', ' ') }}</span>
                                    <span data-output="prix_max">{{ number_format($filters['price_max'] ?? $priceBounds['max'], 0, ' ', ' ') }}</span>
                                </div>
                                <div data-price-range class="mt-2">
                                    <input type="range" name="prix_min" class="form-range"
                                           min="{{ $priceBounds['min'] }}" max="{{ $priceBounds['max'] }}" step="1000"
                                           value="{{ $filters['price_min'] ?? $priceBounds['min'] }}">
                                    <input type="range" name="prix_max" class="form-range"
                                           min="{{ $priceBounds['min'] }}" max="{{ $priceBounds['max'] }}" step="1000"
                                           value="{{ $filters['price_max'] ?? $priceBounds['max'] }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('nav.category') }}</label>
                                @foreach ($categories as $item)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="categorie" id="cat-{{ $item->slug }}"
                                               value="{{ $item->slug }}" @checked(($filters['categorie'] ?? null) === $item->slug)>
                                        <label class="form-check-label" for="cat-{{ $item->slug }}">
                                            {{ $item->name($locale) }}
                                            <span class="count">({{ $item->vehicles_count }})</span>
                                        </label>
                                    </div>
                                @endforeach
                                @if ($filters['categorie'] ?? null)
                                    <a class="small d-inline-block mt-1"
                                       href="{{ route('vehicles.index', ['locale' => $locale] + array_diff_key(request()->query(), ['categorie' => true])) }}">
                                        {{ __('nav.clear') }}
                                    </a>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('nav.transmission') }}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transmission" id="t-man"
                                           value="manuelle" @checked(($filters['transmission'] ?? null) === 'manuelle')>
                                    <label class="form-check-label" for="t-man">{{ __('vehicle.transmission.manuelle') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transmission" id="t-auto"
                                           value="automatique" @checked(($filters['transmission'] ?? null) === 'automatique')>
                                    <label class="form-check-label" for="t-auto">{{ __('vehicle.transmission.automatique') }}</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="places_min">{{ __('nav.seats') }}</label>
                                <select class="form-select" name="places_min" id="places_min">
                                    <option value="">{{ __('nav.any') }}</option>
                                    @foreach ([2, 4, 5, 7] as $seats)
                                        <option value="{{ $seats }}" @selected((int) ($filters['seats_min'] ?? 0) === $seats)>
                                            {{ $seats }}+
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('nav.fuel') }}</label>
                                @foreach (['essence' => __('vehicle.fuel.essence'), 'diesel' => __('vehicle.fuel.diesel'), 'hybride' => __('vehicle.fuel.hybride'), 'electrique' => __('vehicle.fuel.electrique')] as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fuel[]" id="f-{{ $value }}"
                                               value="{{ $value }}" @checked(in_array($value, (array) ($filters['fuel'] ?? []), true))>
                                        <label class="form-check-label" for="f-{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="tri">{{ __('nav.sort') }}</label>
                                <select class="form-select" name="tri" id="tri">
                                    <option value="prix-asc" @selected(($filters['sort'] ?? '') === 'prix-asc')>{{ __('nav.sort_price_asc') }}</option>
                                    <option value="prix-desc" @selected(($filters['sort'] ?? '') === 'prix-desc')>{{ __('nav.sort_price_desc') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">{{ __('nav.apply') }}</button>
                            <a class="btn btn-link w-100 mt-1 d-lg-none" data-filters-close href="#">{{ __('nav.close') }}</a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Résultats ─────────────────────────────────────────────────── --}}
            <div class="col-lg-9">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                    <h1 class="h5 fw-bold mb-0">
                        <span data-results-count>{{ $vehicles->count() }}</span>
                        {{ __('general.vehicles_free', ['count' => $vehicles->count()]) }}
                    </h1>
                    <div class="small text-muted">
                        {{ $period->startsAt->translatedFormat('D d M') }} {{ sprintf('%02dh', $period->startsAtHour()) }}
                        <i class="bi bi-arrow-right mx-1"></i>
                        {{ $period->endsAt->translatedFormat('D d M') }} {{ sprintf('%02dh', $period->endsAtHour()) }}
                    </div>
                </div>

                <div class="text-center py-2 d-none" data-results-loading>
                    <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>

                <div data-results>
                    @include('vehicles.partials.results', [
                        'vehicles' => $vehicles,
                        'period' => $period,
                        'locale' => $locale,
                        'alternatives' => $alternatives,
                        'whatsapp' => $whatsapp,
                    ])
                </div>
            </div>
        </div>
</div>

@endsection
