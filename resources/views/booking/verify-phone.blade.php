@extends('layouts.app')

@section('title', __('booking.step.verify'))

@section('content')

<div class="container py-4" style="max-width: 560px;">

    <x-steps :current="3" />

    <h1 class="h4 fw-bold mb-1">{{ __('booking.verify_title') }}</h1>
    <p class="text-muted mb-4">{{ __('booking.verify_intro', ['phone' => $phone]) }}</p>

    <div class="pdj-card text-center">
        <div class="code-input mb-4" data-code-inputs>
            @for ($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" pattern="\d"
                       data-code-input autocomplete="one-time-code"
                       aria-label="{{ __('booking.code_digit', ['number' => $i + 1]) }}">
            @endfor
        </div>

        <form action="{{ route('booking.verify-phone.store', ['locale' => $locale, 'booking' => $booking]) }}" method="POST">
            @csrf
            {{-- Un seul champ réel, rempli par les six cases : moins de state JS,
                 et le formulaire fonctionne même si le script n'a pas tourné. --}}
            <input type="hidden" name="code" data-code-hidden
                   value="{{ old('code') }}">
            <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('booking.verify_cta') }}</button>
        </form>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-3">
        <form action="{{ route('booking.code.resend', ['locale' => $locale, 'booking' => $booking]) }}" method="POST">
            @csrf
            <input type="hidden" name="canal" value="whatsapp">
            <button type="submit" class="btn btn-link">{{ __('booking.resend_whatsapp') }}</button>
        </form>

        <form action="{{ route('booking.code.resend', ['locale' => $locale, 'booking' => $booking]) }}" method="POST">
            @csrf
            <input type="hidden" name="canal" value="sms">
            <button type="submit" class="btn btn-link">{{ __('booking.resend_sms') }}</button>
        </form>
    </div>

    <p class="small text-muted text-center mt-4 mb-0">{{ __('booking.verify_why') }}</p>
</div>

@endsection
