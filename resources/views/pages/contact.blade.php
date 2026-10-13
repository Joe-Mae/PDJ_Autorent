@extends('pages.layout')

@section('title', __('page.contact_title'))

@section('pageContent')

    <h1 class="h3 fw-bold mb-2">{{ __('page.contact_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.contact_intro') }}</p>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="pdj-card h-100">
                <div class="section-title">{{ __('page.reach_us') }}</div>
                <p class="small text-muted">{{ __('page.agency_intro') }}</p>

                <p class="mb-3">
                    <i class="bi bi-geo-alt me-2"></i>{{ $address }}
                </p>
                <p class="mb-3">
                    <i class="bi bi-telephone me-2"></i>
                    <a href="tel:{{ preg_replace('/\D/', '', $phone) }}">{{ $phone }}</a>
                </p>
                <p class="mb-3">
                    <i class="bi bi-envelope me-2"></i>
                    <a href="mailto:{{ $email }}">{{ $email }}</a>
                </p>
                <div class="section-title">{{ __('page.agency_hours') }}</div>
                <p class="mb-3">
                    <i class="bi bi-clock me-2"></i>{{ $open }} – {{ $close }}
                </p>

                <a class="btn btn-outline-primary w-100 mb-2"
                   href="https://www.google.com/maps/search/?api=1&query={{ urlencode($address) }}"
                   target="_blank" rel="noopener">
                    <i class="bi bi-map me-1"></i>{{ __('booking.open_maps') }}
                </a>
                <a class="btn btn-accent w-100"
                   href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode(__('general.whatsapp_help')) }}"
                   target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i>{{ __('general.contact_whatsapp') }}
                </a>

                <p class="small text-muted mt-3 mb-0">{{ __('page.whatsapp_fast') }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="pdj-card h-100">
                <div class="section-title">{{ __('page.write_us') }}</div>

                @isset($whatsapp_draft)
                    <div class="alert alert-success small">
                        {{ __('page.message_sent', ['phone' => $phone]) }}
                    </div>
                @endisset

                <form action="{{ route('page.contact.store', ['locale' => $locale]) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">{{ __('booking.field_first_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="phone">{{ __('booking.field_phone') }}</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                               id="phone" name="phone" value="{{ old('phone') }}" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">{{ __('booking.field_email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="message">{{ __('page.your_message') }}</label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                                  id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                        @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">{{ __('page.send') }}</button>
                </form>
            </div>
        </div>
    </div>

@endsection
