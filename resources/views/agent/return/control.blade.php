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
                    <label class="form-label d-block">{{ __('agent.visual_inspection') }}</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="damage_present" id="damage-no"
                                   value="0" @checked(old('damage_present', '0') === '0') required>
                            <label class="form-check-label" for="damage-no">{{ __('agent.no_damage') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="damage_present" id="damage-yes"
                                   value="1" @checked(old('damage_present') === '1')>
                            <label class="form-check-label" for="damage-yes">{{ __('agent.damage_found') }}</label>
                        </div>
                    </div>
                    <div class="form-text">{{ __('agent.return_inspection_hint') }}</div>
                </div>

                <div class="pdj-card mb-3" id="damage-block" @if (old('damage_present') !== '1') hidden @endif>
                    <label class="form-label" for="damage_note">{{ __('agent.damage') }}</label>
                    <textarea class="form-control mb-3" id="damage_note" name="damage_note" rows="2"
                              placeholder="{{ __('agent.damage_placeholder') }}">{{ old('damage_note') }}</textarea>

                    <label class="form-label" for="damage-photos">{{ __('agent.damage_photos') }}</label>
                    <input type="file" class="form-control" id="damage-photos" name="photos[]"
                           accept=".jpg,.jpeg,.png" multiple
                           @if (old('damage_present') === '1') required @endif>
                    <div class="form-text">{{ __('agent.photos_same_angles') }}</div>
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

            const damageBlock = document.getElementById('damage-block');
            const damagePhotos = document.getElementById('damage-photos');
            const damageNote = document.getElementById('damage_note');

            const toggleDamage = () => {
                const damaged = document.getElementById('damage-yes').checked;

                damageBlock.hidden = ! damaged;
                damagePhotos.required = damaged;
                damageNote.required = damaged;

                if (! damaged) {
                    damagePhotos.value = '';
                }
            };

            document.querySelectorAll('input[name="damage_present"]')
                .forEach((input) => input.addEventListener('change', toggleDamage));

            toggleDamage();
        </script>
    @endpush
@endsection