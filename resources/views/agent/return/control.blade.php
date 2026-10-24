@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Contrôle de retour')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 1/2</div>
                <h1 class="h4 fw-bold">{{ __('agent.return_control') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->vehicle->fullName() }}</p>
            </div>

            <form method="POST" action="{{ route('agent.return.store', $booking) }}" enctype="multipart/form-data">
                @csrf

                <div class="pdj-card mb-3">
                    <label class="form-label" for="mileage">{{ __('agent.mileage_km') }}</label>
                    <input type="number" class="form-control form-control-lg" id="mileage" name="mileage"
                           value="{{ $booking->mileage_at_pickup ?? 0 }}" min="{{ $booking->mileage_at_pickup ?? 0 }}" required>
                    <div class="form-text">
                        Départ : {{ number_format($booking->mileage_at_pickup ?? 0, 0, ' ', ' ') }} km —
                        parcouru : <span id="kmPar" class="fw-semibold">0</span> km
                    </div>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label">{{ __('agent.fuel_level') }} <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['empty' => 0, 'quarter' => 1, 'half' => 2, 'three_quarters' => 3, 'full' => 4] as $value => $level)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fuel_level" id="fuel-{{ $value }}"
                                       value="{{ $value }}" @checked(($booking->fuel_level_pickup === $value))>
                                <label class="form-check-label" for="fuel-{{ $value }}">{{ __('agent.fuel_'.$value) }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text">{{ __('agent.fuel_mandatory') }}</div>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label">{{ __('agent.photos_mandatory') }}</label>
                    <input type="file" class="form-control" name="photos[]" accept=".jpg,.jpeg,.png" multiple required>
                    <div class="form-text">{{ __('agent.photos_same_angles') }}</div>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label" for="damage_note">{{ __('agent.damage') }}</label>
                    <textarea class="form-control" id="damage_note" name="damage_note" rows="2"
                              placeholder="{{ __('agent.damage_placeholder') }}"></textarea>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label" for="late_fee">{{ __('agent.late_fee_field', ['currency' => app(\App\Services\SettingsService::class)->currency()]) }}</label>
                    <input type="number" class="form-control" id="late_fee" name="late_fee" min="0" step="500" value="0">
                    <div class="form-text">{{ __('agent.late_fee_hint') }}</div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.return.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const kmInput = document.getElementById('mileage');
            const kmOut = document.getElementById('kmPar');
            const kmStart = {{ (int) ($booking->mileage_at_pickup ?? 0) }};
            const refresh = () => { kmOut.textContent = new Intl.NumberFormat('fr-FR').format(Math.max(0, kmInput.value - kmStart)); };
            kmInput.addEventListener('input', refresh);
            refresh();
        </script>
    @endpush
@endsection