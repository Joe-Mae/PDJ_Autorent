@extends('layouts.app')

@section('title', __('booking.step.contact'))

@section('content')

<div class="container py-4" style="max-width: 720px;">

    <x-steps :current="2" />

    <h1 class="h4 fw-bold mb-1">{{ __('booking.contact_title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.contact_intro') }}</p>

    <div class="pdj-card">
        <form action="{{ route('booking.contact.store', ['locale' => $locale, 'booking' => $booking]) }}" method="POST" novalidate>
            @csrf

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="first_name">{{ __('booking.field_first_name') }}</label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                           id="first_name" name="first_name" value="{{ old('first_name', $booking->user?->first_name) }}"
                           autocomplete="given-name" required>
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label" for="last_name">{{ __('booking.field_last_name') }}</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                           id="last_name" name="last_name" value="{{ old('last_name', $booking->user?->last_name) }}"
                           autocomplete="family-name" required>
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- L'âge conditionne l'éligibilité : trois champs plutôt qu'un
                     date-picker, plus rapide au doigt et contrôlable. --}}
                <div class="col-12">
                    <label class="form-label" for="birth_date">{{ __('booking.field_birth_date') }}</label>
                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                           id="birth_date" name="birth_date"
                           value="{{ old('birth_date', $booking->user?->birth_date?->toDateString()) }}"
                           max="{{ today()->subYears(18)->toDateString() }}" required>
                    <div class="form-text">{{ __('booking.field_birth_date_hint', ['age' => app(\App\Services\SettingsService::class)->int('booking.min_age')]) }}</div>
                    @error('birth_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="phone">{{ __('booking.field_phone') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">+228</span>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                               id="phone" name="phone"
                               value="{{ old('phone', $booking->user?->phone) }}"
                               placeholder="90 12 34 56" autocomplete="tel" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-text">{{ __('booking.field_phone_hint') }}</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="email">
                        {{ __('booking.field_email') }}
                        <span class="text-muted fw-normal">({{ __('booking.optional') }})</span>
                    </label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email', $booking->user?->email) }}"
                           autocomplete="email">
                    <div class="form-text">{{ __('booking.field_email_hint') }}</div>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                {{ __('booking.continue') }} <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
    </div>
</div>

@endsection
