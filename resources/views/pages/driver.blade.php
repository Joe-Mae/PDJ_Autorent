@extends('pages.layout')

@section('title', __('driver.title_page'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('driver.title_page') }}</h1>
    <p class="lead text-muted mb-4">{{ __('driver.page_intro') }}</p>

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

    <section id="voiture-chauffeur" class="mb-5">
        <h2 class="h4 fw-bold">{{ __('driver.with_vehicle_title') }}</h2>
        <p class="text-muted">{{ __('driver.with_vehicle_intro') }}</p>

        <div class="pdj-card mb-4 text-center" style="background: var(--pdj-green-light); border-color: rgba(10,110,76,.2);">
            <div class="display-5 fw-bold" style="color: var(--pdj-green);">
                {{ number_format($vehicleDailyPrice, 0, ' ', ' ') }}
            </div>
            <div class="text-muted">{{ __('driver.price_unit') }}</div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="pdj-card h-100">
                    <div class="section-title">{{ __('driver.explain') }}</div>
                    <p class="small mb-2">{{ __('driver.point1') }}</p>
                    <p class="small mb-0">{{ __('driver.point2') }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pdj-card h-100">
                    <div class="section-title">{{ __('driver.faq_title') }}</div>
                    <p class="small mb-0">{{ __('driver.point3') }}</p>
                </div>
            </div>
        </div>

        <x-search-panel :with-driver="true" />
    </section>

    <hr class="my-5">

    <section id="chauffeur-seul">
        <h2 class="h4 fw-bold">{{ __('driver.solo_title') }}</h2>
        <p class="text-muted">
            {{ __('driver.solo_intro', [
                'days' => $soloDiscountAfterDays,
                'discount' => number_format($soloDiscountDailyPrice, 0, ' ', ' '),
            ]) }}
        </p>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="pdj-card h-100">
                    {{ __('driver.solo_rate_standard', [
                        'days' => $soloDiscountAfterDays,
                        'price' => number_format($soloDailyPrice, 0, ' ', ' '),
                    ]) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="pdj-card h-100">
                    {{ __('driver.solo_rate_discount', [
                        'days' => $soloDiscountAfterDays + 1,
                        'price' => number_format($soloDiscountDailyPrice, 0, ' ', ' '),
                    ]) }}
                </div>
            </div>
        </div>

        <div class="pdj-card">
            <h3 class="h5 fw-bold">{{ __('driver.solo_form_title') }}</h3>
            <form action="{{ route('driver-only-bookings.store', ['locale' => $locale]) }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="solo-name">{{ __('driver.solo_name') }}</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="solo-name"
                           name="name" value="{{ old('name') }}" maxlength="120" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="solo-phone">{{ __('driver.solo_phone') }}</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="solo-phone"
                           name="phone" type="tel" value="{{ old('phone') }}" required>
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="solo-start">{{ __('driver.solo_start_date') }}</label>
                    <input class="form-control @error('du') is-invalid @enderror" id="solo-start"
                           name="du" type="date" value="{{ old('du') }}"
                           min="{{ today()->toDateString() }}" required>
                    @error('du') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="solo-end">{{ __('driver.solo_end_date') }}</label>
                    <input class="form-control @error('au') is-invalid @enderror" id="solo-end"
                           name="au" type="date" value="{{ old('au') }}"
                           min="{{ old('du', today()->toDateString()) }}" required>
                    @error('au') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{{ __('driver.solo_submit') }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
