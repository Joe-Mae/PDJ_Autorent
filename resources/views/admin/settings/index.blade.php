@extends('layouts.panel')

@section('title', 'ParamÃ¨tres')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.settings') }}</h1>
        <p class="text-muted small">{{ __('admin.settings_hint') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <div class="row g-4">
            @foreach ($groups as $group)
                <div class="col-lg-4">
                    <div class="pdj-card h-100">
                        <h2 class="h6 fw-bold mb-3">{{ $group['label'] }}</h2>

                        @foreach ($group['settings'] as $setting)
                            <div class="mb-3">
                                <label class="form-label" for="setting-{{ $setting->key }}">{{ $setting->label_fr }}</label>

                                @if ($setting->type === 'bool')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               name="settings[{{ $setting->key }}]" value="1"
                                               id="setting-{{ $setting->key }}"
                                               @checked((bool) $setting->typedValue())>
                                    </div>
                                @elseif ($setting->type === 'int')
                                    <input type="number" class="form-control"
                                           name="settings[{{ $setting->key }}]"
                                           id="setting-{{ $setting->key }}"
                                           value="{{ $setting->value }}"
                                           @if (str_contains($setting->key, 'price') || str_contains($setting->key, 'deposit')) step="500" @elseif (str_contains($setting->key, 'time')) @endif>
                                @else
                                    <input type="text" class="form-control"
                                           name="settings[{{ $setting->key }}]"
                                           id="setting-{{ $setting->key }}"
                                           value="{{ $setting->value }}">
                                @endif

                                @if ($setting->help_fr)
                                    <div class="form-text">{{ $setting->help_fr }}</div>
                                @endif
                                <div class="form-text text-muted font-monospace" style="font-size: 0.7rem;">{{ $setting->key }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            <button class="btn btn-primary btn-lg">{{ __('admin.save_settings') }}</button>
        </div>
    </form>
@endsection