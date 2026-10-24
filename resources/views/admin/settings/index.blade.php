@extends('layouts.panel')

@section('title', __('admin.settings'))
@section('breadcrumb', __('admin.settings'))

@section('content')
    <x-admin.page-header
        :title="__('admin.settings')"
        :subtitle="__('admin.settings_hint')">
        <button class="btn btn-primary" type="submit" form="settings-form">
            <i class="bi bi-check2 me-1"></i>{{ __('admin.save_settings') }}
        </button>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settings-form">
        @csrf

        <div class="row g-4">
            @foreach ($groups as $group)
                @php
                    $icons = ['general' => 'building', 'reservation' => 'calendar-check', 'driver' => 'person-badge'];
                @endphp
                <div class="col-12 col-xl-4">
                    <section class="pdj-card h-100">
                        <div class="admin-section-heading">
                            <div>
                                <span class="admin-eyebrow">{{ __('admin.settings') }}</span>
                                <h2 class="h5 fw-bold mb-0">{{ $group['label'] }}</h2>
                            </div>
                            <span class="admin-stat-card__icon text-primary" style="width: 34px; height: 34px; margin: 0;">
                                <i class="bi bi-{{ $icons[$group['key']] ?? 'gear' }}"></i>
                            </span>
                        </div>

                        @foreach ($group['settings'] as $setting)
                            @php
                                $label = app()->getLocale() === 'en' && $setting->label_en ? $setting->label_en : $setting->label_fr;
                                // Les libellés de réglage monétaire portent le placeholder
                                // :currency — la devise en cours s'y affiche toute seule.
                                $label = str_replace(':currency', app(\App\Services\SettingsService::class)->currency(), (string) $label);
                                $isTime = str_ends_with($setting->key, '_time') || $setting->key === 'booking.free_cancellation_before';
                                $isMoney = str_contains($setting->key, 'price') || str_contains($setting->key, 'deposit');
                            @endphp
                            <div class="mb-3">
                                <label class="form-label" for="setting-{{ $setting->key }}">{{ $label }}</label>

                                @if ($setting->type === 'bool')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               name="settings[{{ $setting->key }}]" value="1"
                                               id="setting-{{ $setting->key }}"
                                               @checked((bool) $setting->typedValue())>
                                        <label class="form-check-label small text-muted" for="setting-{{ $setting->key }}">
                                            {{ __('admin.enabled') }}
                                        </label>
                                    </div>
                                @elseif ($isTime)
                                    <input type="time" class="form-control"
                                           name="settings[{{ $setting->key }}]"
                                           id="setting-{{ $setting->key }}"
                                           value="{{ $setting->value }}">
                                @elseif ($setting->type === 'int')
                                    <input type="number" class="form-control"
                                           name="settings[{{ $setting->key }}]"
                                           id="setting-{{ $setting->key }}"
                                           value="{{ $setting->value }}"
                                           @if ($isMoney) step="500" min="0" @endif>
                                @else
                                    <input type="text" class="form-control"
                                           name="settings[{{ $setting->key }}]"
                                           id="setting-{{ $setting->key }}"
                                           value="{{ $setting->value }}">
                                @endif

                                @if ($setting->help_fr)
                                    <div class="form-text">{{ $setting->help_fr }}</div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                </div>
            @endforeach
        </div>

        <div class="admin-settings-save">
            <a class="btn btn-light border" href="{{ route('admin.dashboard') }}">{{ __('admin.cancel') }}</a>
            <button class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>{{ __('admin.save_settings') }}
            </button>
        </div>
    </form>
@endsection
