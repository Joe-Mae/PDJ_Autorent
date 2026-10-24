@extends('layouts.app')

@section('title', __('account.my_profile'))

@section('content')
    <div class="container py-4" style="max-width: 620px;">
        <x-account-bar />

        <h1 class="h4 fw-bold mb-4">{{ __('account.my_profile') }}</h1>

        <div class="pdj-card mb-3">
            <div class="section-title">{{ __('account.identity') }}</div>
            <form action="{{ route('account.profile.update', ['locale' => $locale]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="first_name">{{ __('booking.field_first_name') }}</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="last_name">{{ __('booking.field_last_name') }}</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="phone">{{ __('booking.field_phone') }}</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">{{ __('booking.field_email') }}</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="birth_date">{{ __('booking.field_birth_date') }}</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date"
                               value="{{ old('birth_date', $user->birth_date?->toDateString()) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="preferred_locale">{{ __('nav.language') }}</label>
                        <select class="form-select" id="preferred_locale" name="preferred_locale">
                            <option value="fr" @selected($user->preferred_locale === 'fr')>Français</option>
                            <option value="en" @selected($user->preferred_locale === 'en')>English</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3">{{ __('admin.save') }}</button>
            </form>
        </div>

        <div class="pdj-card">
            <div class="section-title">{{ __('auth.field_password') }}</div>
            <form action="{{ route('account.password.update', ['locale' => $locale]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="current_password">{{ __('account.current_password') }}</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">{{ __('booking.field_password') }}</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">{{ __('booking.field_password_confirm') }}</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>
                <button class="btn btn-primary w-100">{{ __('admin.save') }}</button>
            </form>
        </div>

        {{-- La déconnexion est dans la barre du haut : la laisser aussi en bas
             de cette page donnerait deux boutons identiques sur un même écran. --}}
    </div>
@endsection