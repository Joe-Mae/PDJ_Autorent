@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'État des lieux')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 2/6</div>
                <h1 class="h4 fw-bold">{{ __('agent.condition_report') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->vehicle->fullName() }}</p>
            </div>

            <form method="POST" action="{{ route('agent.pickup.condition.store', $booking) }}" enctype="multipart/form-data">
                @csrf

                <div class="pdj-card mb-3">
                    <label class="form-label" for="mileage">{{ __('agent.mileage_km') }}</label>
                    <input type="number" class="form-control form-control-lg" id="mileage" name="mileage"
                           value="{{ $booking->vehicle->mileage }}" min="0" required>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label">{{ __('agent.fuel_level') }}</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['quarter' => 1, 'half' => 2, 'three_quarters' => 3, 'full' => 4] as $value => $level)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fuel_level" id="fuel-{{ $value }}"
                                       value="{{ $value }}" @checked($level === 4)>
                                <label class="form-check-label" for="fuel-{{ $value }}">{{ __('agent.fuel_'.$value) }}</label>
                            </div>
                        @endforeach
                    </div>
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
                    <div class="form-text">{{ __('agent.inspection_hint') }}</div>
                </div>

                <div class="pdj-card mb-3" id="damage-block" @if (old('damage_present') !== '1') hidden @endif>
                    <label class="form-label" for="damage_reported">{{ __('agent.existing_damage') }}</label>
                    <textarea class="form-control mb-3" id="damage_reported" name="damage_reported" rows="2"
                              placeholder="{{ __('agent.damage_placeholder') }}">{{ old('damage_reported') }}</textarea>

                    <label class="form-label" for="damage-photos">{{ __('agent.damage_photos') }}</label>
                    <input type="file" class="form-control" id="damage-photos" name="photos[]"
                           accept=".jpg,.jpeg,.png" multiple
                           @if (old('damage_present') === '1') required @endif>
                    <div class="form-text">{{ __('agent.damage_photos_hint') }}</div>
                    <div class="small text-muted mt-2">
                        1. 4 coins w 2. tableau de bord w 3. compteur w 4. jauge de carburant
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const damageBlock = document.getElementById('damage-block');
            const damagePhotos = document.getElementById('damage-photos');
            const damageNote = document.getElementById('damage_reported');

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