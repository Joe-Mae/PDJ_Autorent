@extends('layouts.app')

@section('title', __('nav.login'))

@section('content')

<div class="container py-5" style="max-width: 460px;">

    <div class="text-center mb-4">
        <h1 class="h4 fw-bold">{{ __('nav.login') }}</h1>
        <p class="text-muted">{{ __('auth.login_intro') }}</p>
    </div>

    <div class="pdj-card">
        <form action="{{ route('login.store', ['locale' => $locale]) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="phone">{{ __('auth.phone_or_email') }}</label>
                <input type="text" class="form-control @error('login') is-invalid @enderror"
                       id="phone" name="login" value="{{ old('login') }}"
                       autocomplete="username" required autofocus>
                @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">{{ __('auth.field_password') }}</label>
                <input type="password" class="form-control" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('auth.login_cta') }}</button>
        </form>
    </div>

</div>

@endsection
