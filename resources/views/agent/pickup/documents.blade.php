@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Contrôle des documents')

@section('content')
    @php
        $customerName = $booking->user?->displayName() ?? $booking->customer_name ?? '—';
        $customerPhone = $booking->user?->phone ?? $booking->customer_phone;
        $birthDate = $booking->user?->birth_date;
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 1/6</div>
                <h1 class="h4 fw-bold">{{ __('agent.check_originals') }}</h1>
                <p class="text-muted">{{ $booking->reference }} — {{ $booking->vehicle->displayName() }}</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('agent.pickup.documents.store', $booking) }}" enctype="multipart/form-data">
                @csrf

                <div class="pdj-card mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $customerName }}</div>
                            @if ($customerPhone)
                                <a class="small" href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                            @endif
                            @if ($birthDate)
                                <div class="small text-muted">
                                    {{ $birthDate->format('d/m/Y') }} ({{ $booking->user->age() }} ans)
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pdj-card mb-3">
                    <h2 class="h6 fw-bold">{{ __('agent.identity_check') }}</h2>
                    <p class="small text-muted">{{ __('agent.original_identity_required') }}</p>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="identity_original_verified" value="1"
                               id="identity_original_verified" @checked(old('identity_original_verified', $booking->identity_original_verified))>
                        <label class="form-check-label" for="identity_original_verified">
                            {{ __('agent.identity_original_seen') }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="identity_name_matches" value="1"
                               id="identity_name_matches" @checked(old('identity_name_matches', $booking->identity_name_matches))>
                        <label class="form-check-label" for="identity_name_matches">
                            {{ __('agent.identity_name_matches', ['name' => $customerName]) }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="customer_age_eligible" value="1"
                               id="customer_age_eligible" @checked(old('customer_age_eligible', $booking->customer_age_eligible))>
                        <label class="form-check-label" for="customer_age_eligible">
                            {{ __('agent.age_requirement_met', ['age' => $minimumAge]) }}
                        </label>
                    </div>
                </div>

                @if ($booking->with_driver)
                    <div class="alert alert-success small">
                        <i class="bi bi-person-check me-1"></i>{{ __('driver.no_license_needed') }}
                    </div>
                @else
                    <div class="pdj-card mb-3">
                        <h2 class="h6 fw-bold">{{ __('agent.license_check') }}</h2>
                        <p class="small text-muted">{{ __('agent.original_license_required') }}</p>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="driver_license_original_verified" value="1"
                                   id="driver_license_original_verified"
                                   @checked(old('driver_license_original_verified', $booking->driver_license_original_verified))>
                            <label class="form-check-label" for="driver_license_original_verified">
                                {{ __('agent.license_original_seen') }}
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="driver_license_category">{{ __('agent.license_category') }}</label>
                            <input class="form-control" type="text" id="driver_license_category" name="driver_license_category"
                                   value="{{ old('driver_license_category', $booking->driver_license_category) }}" maxlength="30">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="driver_license_category_authorized" value="1"
                                   id="driver_license_category_authorized"
                                   @checked(old('driver_license_category_authorized', $booking->driver_license_category_authorized))>
                            <label class="form-check-label" for="driver_license_category_authorized">
                                {{ __('agent.license_category_authorized') }}
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="driver_license_issued_at">{{ __('agent.license_issued_at') }}</label>
                                <input class="form-control" type="date" id="driver_license_issued_at" name="driver_license_issued_at"
                                       value="{{ old('driver_license_issued_at', $booking->driver_license_issued_at?->format('Y-m-d')) }}">
                                <div class="form-text">{{ __('agent.license_tenure_hint', ['years' => $minimumLicenseYears]) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="driver_license_expires_at">{{ __('agent.license_expires_at') }}</label>
                                <input class="form-control" type="date" id="driver_license_expires_at" name="driver_license_expires_at"
                                       value="{{ old('driver_license_expires_at', $booking->driver_license_expires_at?->format('Y-m-d')) }}">
                                <div class="form-text">{{ __('agent.license_valid_through', ['date' => $booking->ends_at->format('d/m/Y')]) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="pdj-card mb-3">
                        <label class="form-label" for="license_copy">{{ __('agent.license_copy') }}</label>
                        <input class="form-control" type="file" id="license_copy" name="license_copy"
                               accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="license_copy_consent" value="1"
                                   id="license_copy_consent" @checked(old('license_copy_consent'))>
                            <label class="form-check-label small" for="license_copy_consent">
                                {{ __('agent.license_copy_consent') }}
                            </label>
                        </div>
                        <div class="form-text">{{ __('agent.license_copy_hint') }}</div>
                    </div>
                @endif

                <div class="pdj-card mb-3">
                    <label class="form-label" for="verification_note">{{ __('agent.verification_note') }}</label>
                    <textarea class="form-control" id="verification_note" name="verification_note" rows="2">{{ old('verification_note') }}</textarea>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.save_document_check') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
