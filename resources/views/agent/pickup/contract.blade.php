@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Contrat')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 5/5</div>
                <h1 class="h4 fw-bold">{{ __('agent.contract') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->user?->displayName() }}</p>
            </div>

            <div class="pdj-card mb-3" style="max-height: 40vh; overflow-y: auto;">
                <h2 class="h6 fw-bold">{{ __('agent.conditions_title') }}</h2>
                <ul class="small">
                    <li>{{ __('agent.condition_age') }}</li>
                    <li>{{ __('agent.condition_km') }}</li>
                    <li>{{ __('agent.condition_fuel') }}</li>
                    <li>{{ __('agent.condition_speed', ['speed' => $speedLimit]) }}</li>
                    <li>{{ __('agent.condition_drivers') }}</li>
                    <li>{{ __('agent.condition_border') }}</li>
                    <li>{{ __('agent.condition_animals') }}</li>
                    <li>{{ __('agent.condition_insurance') }}</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('agent.pickup.contract.store', $booking) }}">
                @csrf
                <div class="pdj-card mb-3">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="accepted_conditions" value="1"
                               id="accepted" required>
                        <label class="form-check-label" for="accepted">
                            {{ __('agent.read_and_accepted') }}
                        </label>
                    </div>
                    <label class="form-label" for="signature">{{ __('agent.signature') }}</label>
                    <input type="text" class="form-control form-control-lg" id="signature" name="signature"
                           placeholder="{{ __('agent.signature_placeholder') }}" required>
                    <div class="form-text">{{ __('agent.signature_hint') }}</div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection