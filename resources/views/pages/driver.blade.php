@extends('pages.layout')

@section('title', __('driver.title_page'))

@section('pageContent')
    @php
        $activeTab = $errors->any() || session('driver_only_booking_reference') ? 'driver-only' : 'vehicle-driver';
        $settings = app(\App\Services\SettingsService::class);
        $startDate = today()->addDay()->toDateString();
        $returnDate = today()->addDay()->addDays(3)->toDateString();
        $hours = range($settings->openHour(), $settings->closeHour());
    @endphp

    <div class="driver-page">
        <header class="driver-page__header">
            <h1 class="h2 fw-bold mb-2">{{ __('driver.title_page') }}</h1>
            <p class="text-muted mb-3">{{ __('driver.page_intro') }}</p>
            <div class="driver-page__rate">
                <strong>{{ number_format($vehicleDailyPrice, 0, ' ', ' ') }} {{ __('driver.price_unit') }}</strong>
            </div>
        </header>

        @if (session('driver_only_booking_reference'))
            <div class="alert alert-success">
                {{ __('driver.solo_received', [
                    'reference' => session('driver_only_booking_reference'),
                    'days' => session('driver_only_booking_days'),
                    'daily_price' => number_format(session('driver_only_booking_daily_price'), 0, ' ', ' '),
                    'total' => number_format(session('driver_only_booking_total'), 0, ' ', ' '),
                ]) }}
            </div>
        @endif

        <ul class="nav nav-pills driver-page__tabs mb-3" role="tablist" aria-label="{{ __('driver.title_page') }}">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link {{ $activeTab === 'vehicle-driver' ? 'active' : '' }}"
                    id="vehicle-driver-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#vehicle-driver-panel"
                    type="button"
                    role="tab"
                    aria-controls="vehicle-driver-panel"
                    aria-selected="{{ $activeTab === 'vehicle-driver' ? 'true' : 'false' }}"
                >
                    {{ __('driver.with_vehicle_tab') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link {{ $activeTab === 'driver-only' ? 'active' : '' }}"
                    id="driver-only-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#driver-only-panel"
                    type="button"
                    role="tab"
                    aria-controls="driver-only-panel"
                    aria-selected="{{ $activeTab === 'driver-only' ? 'true' : 'false' }}"
                >
                    {{ __('driver.solo_title') }}
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <section
                class="tab-pane fade {{ $activeTab === 'vehicle-driver' ? 'show active' : '' }}"
                id="vehicle-driver-panel"
                role="tabpanel"
                aria-labelledby="vehicle-driver-tab"
                tabindex="0"
            >
                <div class="driver-page__card">
                    <div class="search-panel__label">{{ __('driver.with_vehicle_search_title') }}</div>
                    <p class="small text-muted mb-3">{{ __('driver.with_vehicle_search_intro') }}</p>

                    <form action="{{ route('vehicles.index', ['locale' => $locale]) }}" method="GET" data-driver-price-form>
                        <input type="hidden" name="avec_chauffeur" value="1">

                        <div class="driver-page__date-group">
                            <h2 class="h6 fw-bold mb-3">{{ __('general.search_pickup') }}</h2>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-uppercase" for="driver-start-date">{{ __('driver.date_label') }}</label>
                                    <input
                                        class="form-control"
                                        id="driver-start-date"
                                        name="du"
                                        data-price-start
                                        type="date"
                                        value="{{ request('du', $startDate) }}"
                                        min="{{ today()->toDateString() }}"
                                        required
                                    >
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-uppercase" for="driver-start-hour">{{ __('driver.time_label') }}</label>
                                    <select class="form-select" id="driver-start-hour" name="heure_depart">
                                        @foreach ($hours as $hour)
                                            <option value="{{ $hour }}" @selected((int) request('heure_depart', $settings->openHour()) === $hour)>
                                                {{ sprintf('%02dh00', $hour) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="driver-page__date-group">
                            <h2 class="h6 fw-bold mb-3">{{ __('general.search_return') }}</h2>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-uppercase" for="driver-return-date">{{ __('driver.date_label') }}</label>
                                    <input
                                        class="form-control"
                                        id="driver-return-date"
                                        name="au"
                                        data-price-end
                                        type="date"
                                        value="{{ request('au', $returnDate) }}"
                                        min="{{ request('du', $startDate) }}"
                                        required
                                    >
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-uppercase" for="driver-return-hour">{{ __('driver.time_label') }}</label>
                                    <select class="form-select" id="driver-return-hour" name="heure_retour">
                                        @foreach ($hours as $hour)
                                            <option value="{{ $hour }}" @selected((int) request('heure_retour', $settings->closeHour()) === $hour)>
                                                {{ sprintf('%02dh00', $hour) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="driver-page__price-preview mb-3"
                            data-driver-price-preview
                            data-daily-rate="{{ $vehicleDailyPrice }}"
                            data-daily-label="{{ __('driver.price_calculation', ['days' => '__DAYS__', 'daily_price' => '__DAILY__']) }}"
                            data-empty-label="{{ __('driver.price_select_dates') }}"
                            data-currency="F CFA"
                            aria-live="polite"
                        >
                            <span class="driver-page__price-empty" data-price-empty>{{ __('driver.price_select_dates') }}</span>
                            <span data-price-breakdown hidden></span>
                            <strong data-price-total hidden></strong>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3">
                            {{ __('general.search_cta') }} <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <p class="small text-muted text-center mt-3 mb-0">
                        <i class="bi bi-geo-alt me-1"></i>{{ __('general.search_agency_note', ['city' => 'Lomé']) }}
                    </p>
                </div>
            </section>

            <section
                class="tab-pane fade {{ $activeTab === 'driver-only' ? 'show active' : '' }}"
                id="driver-only-panel"
                role="tabpanel"
                aria-labelledby="driver-only-tab"
                tabindex="0"
            >
                <div class="driver-page__card">
                    <div class="search-panel__label">{{ __('driver.solo_form_title') }}</div>
                    <p class="small text-muted mb-3">
                        {{ __('driver.solo_intro', [
                            'days' => $soloDiscountAfterDays,
                            'discount' => number_format($soloDiscountDailyPrice, 0, ' ', ' '),
                        ]) }}
                    </p>

                    <div class="driver-page__solo-rates mb-4">
                        <span>{{ __('driver.solo_rate_standard', [
                            'days' => $soloDiscountAfterDays,
                            'price' => number_format($soloDailyPrice, 0, ' ', ' '),
                        ]) }}</span>
                        <span>{{ __('driver.solo_rate_discount', [
                            'days' => $soloDiscountAfterDays + 1,
                            'price' => number_format($soloDiscountDailyPrice, 0, ' ', ' '),
                        ]) }}</span>
                    </div>

                    <form action="{{ route('driver-only-bookings.store', ['locale' => $locale]) }}" method="POST" class="row g-3" data-driver-price-form>
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="solo-name">{{ __('driver.solo_name') }}</label>
                            <input class="form-control @error('name') is-invalid @enderror" id="solo-name"
                                   name="name" value="{{ old('name') }}" maxlength="120" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="solo-phone">{{ __('driver.solo_phone') }}</label>
                            <input class="form-control @error('phone') is-invalid @enderror" id="solo-phone"
                                   name="phone" type="tel" value="{{ old('phone') }}" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="solo-start">{{ __('driver.solo_start_date') }}</label>
                            <input class="form-control @error('du') is-invalid @enderror" id="solo-start"
                                   name="du" type="date" data-price-start value="{{ old('du') }}"
                                   min="{{ today()->toDateString() }}" required>
                            @error('du') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="solo-end">{{ __('driver.solo_end_date') }}</label>
                            <input class="form-control @error('au') is-invalid @enderror" id="solo-end"
                                   name="au" type="date" data-price-end value="{{ old('au') }}"
                                   min="{{ old('du', today()->toDateString()) }}" required>
                            @error('au') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div
                            class="col-12 driver-page__price-preview"
                            data-driver-price-preview
                            data-daily-rate="{{ $soloDailyPrice }}"
                            data-discount-daily-rate="{{ $soloDiscountDailyPrice }}"
                            data-discount-after-days="{{ $soloDiscountAfterDays }}"
                            data-daily-label="{{ __('driver.price_calculation', ['days' => '__DAYS__', 'daily_price' => '__DAILY__']) }}"
                            data-empty-label="{{ __('driver.price_select_dates') }}"
                            data-currency="F CFA"
                            aria-live="polite"
                        >
                            <span class="driver-page__price-empty" data-price-empty>{{ __('driver.price_select_dates') }}</span>
                            <span data-price-breakdown hidden></span>
                            <strong data-price-total hidden></strong>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">{{ __('driver.solo_submit') }}</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
