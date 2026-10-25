@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Remise des clés')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 6/6</div>
                <div class="d-grid mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; background: var(--pdj-green-light); place-items: center;">
                    <i class="bi bi-key fs-2" style="color: var(--pdj-green);"></i>
                </div>
                <h1 class="h4 fw-bold">{{ __('agent.handover') }}</h1>
                <p class="text-muted">{{ $booking->reference }}</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="pdj-card mb-3">
                <table class="table admin-table mb-0">
                    <tbody>
                        <tr><td>{{ __('admin.vehicle') }}</td><td class="text-end fw-semibold">{{ $booking->vehicle->fullName() }}</td></tr>
                        <tr><td>{{ __('admin.plate') }}</td><td class="text-end font-monospace">{{ $booking->vehicle->plate }}</td></tr>
                        <tr><td>{{ __('admin.client') }}</td><td class="text-end">{{ $booking->user?->displayName() ?? $booking->customer_name ?? '-' }}</td></tr>
                        <tr><td>{{ __('agent.mileage_km') }}</td><td class="text-end">{{ number_format($booking->mileage_at_pickup ?? 0, 0, ' ', ' ') }} km</td></tr>
                        <tr><td>{{ __('agent.fuel_level') }}</td><td class="text-end">{{ $booking->fuel_level_pickup ? __('agent.fuel_'.$booking->fuel_level_pickup) : '-' }}</td></tr>
                    </tbody>
                </table>
            </div>

            @if (! $identityReady)
                <div class="alert alert-danger small">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ __('agent.handover_blocked') }}
                </div>
                <a class="btn btn-outline-primary w-100" href="{{ route('agent.pickup.documents', $booking) }}">
                    {{ __('agent.back_to_documents') }}
                </a>
            @else
                <form method="POST" action="{{ route('agent.pickup.complete', $booking) }}" enctype="multipart/form-data">
                    @csrf

                    @if ($needsLicense)
                        <div class="pdj-card mb-3">
                            <h2 class="h6 fw-bold">{{ __('agent.license_check') }}</h2>
                            <p class="small text-muted">{{ __('agent.handover_license_hint') }}</p>

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
                            @if ($licenseDocument)
                                <a href="{{ route('admin.documents.show', $licenseDocument) }}" target="_blank"
                                   rel="noopener" class="d-block mb-2 text-decoration-none">
                                    @if ($licenseDocument->mime_type === 'application/pdf')
                                        <div class="border rounded p-3 text-center">
                                            <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                                            <div class="small mt-1">{{ __('admin.open_document') }}</div>
                                        </div>
                                    @else
                                        <img src="{{ $licenseDocument->temporaryUrl() }}"
                                             alt="{{ $licenseDocument->type->label() }}"
                                             class="img-fluid rounded border" style="max-height: 200px;">
                                    @endif
                                </a>
                                <div class="alert alert-success small">
                                    <i class="bi bi-check-circle me-1"></i>{{ __('agent.license_copy_on_file') }}
                                </div>
                            @endif

                            <label class="form-label fw-semibold" for="license_copy">
                                {{ $hasLicenseCopy ? __('agent.license_photo_replace') : __('agent.license_photo') }}
                            </label>
                            <input class="form-control" type="file" id="license_copy" name="license_copy"
                                   accept="image/*" capture="environment" @required(! $hasLicenseCopy)>
                            <div class="form-text">{{ __('agent.license_photo_hint') }}</div>

                            @unless ($hasLicenseCopy)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="license_copy_consent" value="1"
                                           id="license_copy_consent" @checked(old('license_copy_consent')) required>
                                    <label class="form-check-label small" for="license_copy_consent">
                                        {{ __('agent.license_copy_consent') }}
                                    </label>
                                </div>
                            @endunless
                        </div>

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>{{ __('agent.handover_auto_start') }}
                        </div>
                    @else
                        <div class="alert alert-success small">
                            <i class="bi bi-check-circle me-1"></i>{{ __('agent.before_keys') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <button class="btn btn-primary btn-lg w-100">
                        {{ $needsLicense ? __('agent.verify_license_and_give_keys') : __('agent.give_keys') }}
                    </button>
                </form>
            @endif

            <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
        </div>
    </div>
@endsection
