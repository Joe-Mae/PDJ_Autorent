@extends('pages.layout')

@section('title', __('driver.title_page'))

@section('pageContent')
    @php
        $settings = app(\App\Services\SettingsService::class);
        $money = fn (int $amount) => $settings->money($amount);
        $currency = $settings->currency();
        $pageIntro = $settings->string('content.driver.page_intro.'.app()->getLocale())
            ?: __('driver.page_intro');
        $occasions = [
            ['icon' => 'heart-fill', 'key' => 'wedding'],
            ['icon' => 'stars', 'key' => 'events'],
            ['icon' => 'briefcase-fill', 'key' => 'business'],
            ['icon' => 'cart3', 'key' => 'errands'],
        ];
    @endphp

    <div class="driver-page">
        <header class="driver-page__header">
            <h1>{{ __('driver.hero_title') }}</h1>
            <p>{{ $pageIntro }}</p>
        </header>

        @if (session('driver_only_booking_reference'))
            <div class="alert alert-success">
                {{ __('driver.solo_received', [
                    'reference' => session('driver_only_booking_reference'),
                    'days' => session('driver_only_booking_days'),
                    'daily_price' => $money((int) session('driver_only_booking_daily_price')),
                    'total' => $money((int) session('driver_only_booking_total')),
                ]) }}
                <div class="mt-1 small">
                    <a href="{{ route('booking.lookup', ['locale' => $locale]) }}">{{ __('booking.lookup.title') }}</a>
                </div>
            </div>
        @endif

        {{-- Les deux formules, côte à côte : chacune mène directement à son
             parcours (la flotte pour la voiture avec chauffeur, le formulaire
             ci-dessous pour le chauffeur seul). --}}
        <div class="driver-offers">
            <article class="driver-offer">
                {{-- La voiture et son conducteur : le petit badge doré dit
                     « avec chauffeur » là où la seule voiture ne le ferait pas. --}}
                <span class="driver-offer__icon">
                    <i class="bi bi-car-front-fill" aria-hidden="true"></i>
                    <i class="bi bi-person-fill driver-offer__icon-badge" aria-hidden="true"></i>
                </span>
                <h2>{{ __('driver.offer_vehicle_title') }}</h2>
                <p class="driver-offer__text">{{ __('driver.offer_vehicle_text') }}</p>
                <p class="driver-offer__price">
                    + {{ $money($vehicleDailyPrice) }} <span>/ {{ __('driver.per_day') }}</span>
                </p>
                <p class="driver-offer__note">{{ __('driver.offer_vehicle_note') }}</p>
                <a
                    class="btn driver-offer__cta driver-offer__cta--accent"
                    href="{{ route('vehicles.index', ['locale' => $locale, 'avec_chauffeur' => 1]) }}"
                >
                    {{ __('driver.offer_vehicle_cta') }}
                </a>
            </article>

            <article class="driver-offer">
                <span class="driver-offer__icon driver-offer__icon--solo">
                    <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                </span>
                <h2>{{ __('driver.offer_solo_title') }}</h2>
                <p class="driver-offer__text">{{ __('driver.offer_solo_text') }}</p>
                <p class="driver-offer__price">
                    {{ $money($soloDailyPrice) }} <span>/ {{ __('driver.per_day') }}</span>
                </p>
                @if ($soloDiscountDailyPrice > 0 && $soloDiscountDailyPrice < $soloDailyPrice)
                    <p class="driver-offer__note">
                        <i class="bi bi-percent" aria-hidden="true"></i>
                        {{ __('driver.offer_solo_note', [
                            'discount' => $money($soloDiscountDailyPrice),
                            'days' => $soloDiscountAfterDays + 1,
                        ]) }}
                    </p>
                @endif
                <a class="btn driver-offer__cta driver-offer__cta--dark" href="#chauffeur-seul">
                    {{ __('driver.offer_solo_cta') }}
                </a>
            </article>
        </div>

        <section class="driver-occasions" aria-labelledby="driver-occasions-title">
            <h2 class="driver-occasions__title" id="driver-occasions-title">
                {{ __('driver.occasions_title') }}
            </h2>

            <div class="row g-3">
                @foreach ($occasions as $occasion)
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="driver-occasion">
                            <i class="bi bi-{{ $occasion['icon'] }}" aria-hidden="true"></i>
                            <h3>{{ __('driver.occasion_'.$occasion['key'].'_title') }}</h3>
                            <p>{{ __('driver.occasion_'.$occasion['key'].'_text') }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="driver-booking" id="chauffeur-seul" aria-labelledby="driver-booking-title">
            <div class="driver-booking__pitch">
                <span class="driver-booking__eyebrow">{{ __('driver.offer_solo_title') }}</span>
                <h2 class="h3 fw-bold" id="driver-booking-title">{{ __('driver.solo_form_title') }}</h2>
                <p class="driver-booking__intro">{{ __('driver.solo_intro') }}</p>

                <ul class="driver-booking__rates">
                    <li>
                        <i class="bi bi-check2" aria-hidden="true"></i>
                        {{ __('driver.solo_rate_standard', [
                            'days' => $soloDiscountAfterDays,
                            'price' => $money($soloDailyPrice),
                        ]) }}
                    </li>
                    <li>
                        <i class="bi bi-check2" aria-hidden="true"></i>
                        {{ __('driver.solo_rate_discount', [
                            'days' => $soloDiscountAfterDays + 1,
                            'price' => $money($soloDiscountDailyPrice),
                        ]) }}
                    </li>
                </ul>
            </div>

            <form
                action="{{ route('driver-only-bookings.store', ['locale' => $locale]) }}"
                method="POST"
                class="row g-3"
                enctype="multipart/form-data"
                data-driver-price-form
            >
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label" for="solo-name">{{ __('driver.solo_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="solo-name"
                           name="name" value="{{ old('name') }}" maxlength="120" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="solo-phone">{{ __('driver.solo_phone') }}</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="solo-phone"
                           name="phone" type="tel" value="{{ old('phone') }}" required>
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="solo-start">{{ __('driver.solo_start_date') }}</label>
                    <input class="form-control @error('du') is-invalid @enderror" id="solo-start"
                           name="du" type="date" data-price-start value="{{ old('du') }}"
                           min="{{ today()->toDateString() }}" required>
                    @error('du') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="solo-end">{{ __('driver.solo_end_date') }}</label>
                    <input class="form-control @error('au') is-invalid @enderror" id="solo-end"
                           name="au" type="date" data-price-end value="{{ old('au') }}"
                           min="{{ old('du', today()->toDateString()) }}" required>
                    @error('au') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="solo-identity">{{ __('driver.solo_identity') }}</label>
                    <input class="form-control @error('identity_document') is-invalid @enderror" id="solo-identity"
                           name="identity_document" type="file"
                           accept="image/jpeg,image/png,application/pdf" required>
                    <div class="form-text">{{ __('driver.solo_identity_hint') }}</div>
                    @error('identity_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div
                    class="col-12 driver-page__price-preview"
                    data-driver-price-preview
                    data-daily-rate="{{ $soloDailyPrice }}"
                    data-discount-daily-rate="{{ $soloDiscountDailyPrice }}"
                    data-discount-after-days="{{ $soloDiscountAfterDays }}"
                    data-daily-label="{{ __('driver.price_calculation', ['days' => '__DAYS__', 'daily_price' => '__DAILY__', 'currency' => $currency]) }}"
                    data-empty-label="{{ __('driver.price_select_dates') }}"
                    data-currency="{{ $currency }}"
                    aria-live="polite"
                >
                    <span class="driver-page__price-empty" data-price-empty>{{ __('driver.price_select_dates') }}</span>
                    <span data-price-breakdown hidden></span>
                    <strong data-price-total hidden></strong>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100 py-3">{{ __('driver.solo_submit') }}</button>
                </div>
            </form>
        </section>
    </div>

    @if ($errors->any())
        @push('scripts')
            <script>
                // Le formulaire vit sous les deux offres : après un refus de
                // validation, on y amène directement le visiteur plutôt que de
                // le laisser en haut de page sans voir les messages.
                document.getElementById('chauffeur-seul')?.scrollIntoView({ block: 'start' });
            </script>
        @endpush
    @endif
@endsection
