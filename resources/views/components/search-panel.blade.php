@php
    /**
     * Bloc de recherche. Le même composant sert sur l'accueil, sur une page
     * catégorie et dans l'espace client : une seule implémentation, donc une
     * seule règle de validation à maintenir.
     *
     * @var \App\Support\BookingPeriod|null $period
     * @var bool $withDriver
     */
    $locale = app()->getLocale();
    $action = $action ?? route('vehicles.index', ['locale' => $locale]);
    $settings = app(\App\Services\SettingsService::class);

    $startDate = ($period ?? null)?->startsAtDate() ?? today()->addDay()->toDateString();
    $endDate = ($period ?? null)?->endsAtDate() ?? today()->addDay()->addDays(3)->toDateString();
    $startHour = ($period ?? null)?->startsAtHour() ?? $settings->openHour();
    $endHour = ($period ?? null)?->endsAtHour() ?? $settings->closeHour();
    $withDriver = $withDriver ?? false;

    $hours = range($settings->openHour(), $settings->closeHour());
@endphp

<div class="search-panel">
    <form action="{{ $action }}" method="GET" class="row g-3">
        <div class="col-12">
            <div class="search-panel__label">{{ __('general.search_title') }}</div>
            <p class="mb-0 text-muted small">{{ __('general.search_intro') }}</p>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label" for="du">{{ __('general.search_pickup') }}</label>
            <input type="date" class="form-control" id="du" name="du"
                   value="{{ $startDate }}"
                   min="{{ today()->toDateString() }}" required>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label" for="au">{{ __('general.search_return') }}</label>
            <input type="date" class="form-control" id="au" name="au"
                   value="{{ $endDate }}"
                   min="{{ $startDate }}" required>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label" for="heure_depart">{{ __('general.search_hour') }}</label>
            <select class="form-select" id="heure_depart" name="heure_depart">
                @foreach ($hours as $hour)
                    <option value="{{ $hour }}" @selected($startHour === $hour)>
                        {{ sprintf('%02dh00', $hour) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label" for="heure_retour">{{ __('general.search_hour') }}</label>
            <select class="form-select" id="heure_retour" name="heure_retour">
                @foreach ($hours as $hour)
                    <option value="{{ $hour }}" @selected($endHour === $hour)>
                        {{ sprintf('%02dh00', $hour) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="avec_chauffeur" value="1"
                       id="avec_chauffeur" @checked($withDriver)>
                <label class="form-check-label" for="avec_chauffeur">
                    {{ __('general.search_driver') }}
                </label>
            </div>
        </div>

        {{-- Les filtres actifs sont conservés quand on relance une recherche. --}}
        @foreach (['categorie', 'transmission', 'tri', 'places_min', 'prix_max'] as $hidden)
            @if (request($hidden))
                <input type="hidden" name="{{ $hidden }}" value="{{ request($hidden) }}">
            @endif
        @endforeach

        <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <button type="submit" class="btn btn-primary btn-lg px-4">
                {{ __('general.search_cta') }} <i class="bi bi-arrow-right ms-1"></i>
            </button>

            <p class="mb-0 text-muted small">
                <i class="bi bi-geo-alt me-1"></i>{{ __('general.search_agency_note', ['city' => 'Lomé']) }}
            </p>
        </div>
    </form>
</div>
