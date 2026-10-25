@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Contrôle de l\'identité')

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
                <h1 class="h4 fw-bold">{{ __('agent.identity_check') }}</h1>
                <p class="text-muted">{{ $booking->reference }} — {{ $booking->vehicle->displayName() }}</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('agent.pickup.documents.store', $booking) }}">
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

                @if (! $booking->with_driver)
                    <div class="alert alert-info small">
                        <i class="bi bi-card-checklist me-1"></i>{{ __('agent.license_checked_at_handover') }}
                    </div>
                @else
                    <div class="alert alert-success small">
                        <i class="bi bi-person-check me-1"></i>{{ __('driver.no_license_needed') }}
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
