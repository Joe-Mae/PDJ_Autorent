@extends('layouts.app')

@section('title', __('nav.register'))

@section('content')

<div class="container py-5" style="max-width: 520px;">

    <div class="text-center mb-4">
        <h1 class="h4 fw-bold">{{ __('nav.register') }}</h1>
        <p class="text-muted">{{ __('auth.register_intro') }}</p>
    </div>

    <div class="pdj-card">
        <form action="{{ route('register.store', ['locale' => $locale]) }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="first_name">{{ __('booking.field_first_name') }}</label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                           id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label" for="last_name">{{ __('booking.field_last_name') }}</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                           id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="phone">{{ __('booking.field_phone') }}</label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                           id="phone" name="phone" value="{{ old('phone') }}"
                           placeholder="+228 90 12 34 56" required>
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="email">
                        {{ __('booking.field_email') }}
                        <span class="text-muted fw-normal">({{ __('booking.optional') }})</span>
                    </label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="birth_date">{{ __('booking.field_birth_date') }}</label>
                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                           id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required>
                    @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label" for="password">{{ __('booking.field_password') }}</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" autocomplete="new-password" minlength="8" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label" for="password_confirmation">{{ __('booking.field_password_confirm') }}</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" autocomplete="new-password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">{{ __('auth.register_cta') }}</button>
        </form>
    </div>

    <p class="text-center text-muted small mt-3 mb-0">
        {{ __('auth.has_account') }}
        <a href="{{ route('login', ['locale' => $locale]) }}">{{ __('nav.login') }}</a>
    </p>
</div>

@endsection
