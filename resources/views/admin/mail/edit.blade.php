@extends('layouts.panel')

@section('title', __('admin.mail_settings'))
@section('breadcrumb', __('admin.mail_settings'))

@section('content')
    <x-admin.page-header
        :title="__('admin.mail_settings')"
        :subtitle="__('admin.mail_settings_hint')">
        <span @class([
            'badge',
            'text-bg-success' => $configured,
            'text-bg-secondary' => ! $configured,
        ])>
            <i class="bi bi-{{ $configured ? 'check-circle' : 'exclamation-circle' }} me-1"></i>
            {{ $configured ? __('admin.mail_configured') : __('admin.mail_not_configured') }}
        </span>
        <button class="btn btn-primary" type="submit" form="mail-settings-form">
            <i class="bi bi-check2 me-1"></i>{{ __('admin.save_settings') }}
        </button>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.mail.update') }}" id="mail-settings-form">
        @csrf

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.mail_smtp') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.mail_server') }}</h2>
                        </div>
                        <span class="admin-stat-card__icon text-primary" style="width: 34px; height: 34px; margin: 0;">
                            <i class="bi bi-hdd-network"></i>
                        </span>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" value="1"
                               id="mail-enabled" name="enabled" @checked(old('enabled', $enabled))>
                        <label class="form-check-label" for="mail-enabled">
                            {{ __('admin.mail_enable') }}
                        </label>
                        <div class="form-text">{{ __('admin.mail_enable_hint') }}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="mail-host">{{ __('admin.mail_host') }}</label>
                            <input type="text" class="form-control @error('host') is-invalid @enderror"
                                   id="mail-host" name="host" value="{{ old('host', $host) }}"
                                   placeholder="smtp.gmail.com" autocomplete="off">
                            <div class="form-text">{{ __('admin.mail_host_hint') }}</div>
                            @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label" for="mail-port">{{ __('admin.mail_port') }}</label>
                            <input type="number" class="form-control @error('port') is-invalid @enderror"
                                   id="mail-port" name="port" value="{{ old('port', $port) }}"
                                   min="1" max="65535">
                            @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="mail-encryption">{{ __('admin.mail_encryption') }}</label>
                            <select class="form-select @error('encryption') is-invalid @enderror"
                                    id="mail-encryption" name="encryption">
                                <option value="tls" @selected(old('encryption', $encryption) === 'tls')>
                                    {{ __('admin.mail_encryption_tls') }}
                                </option>
                                <option value="ssl" @selected(old('encryption', $encryption) === 'ssl')>
                                    {{ __('admin.mail_encryption_ssl') }}
                                </option>
                                <option value="none" @selected(old('encryption', $encryption) === 'none')>
                                    {{ __('admin.mail_encryption_none') }}
                                </option>
                            </select>
                            @error('encryption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="mail-username">{{ __('admin.mail_username') }}</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                   id="mail-username" name="username" value="{{ old('username', $username) }}"
                                   autocomplete="off">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="mail-password">{{ __('admin.mail_password') }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="mail-password" name="password" value=""
                                   autocomplete="new-password"
                                   placeholder="{{ $hasPassword ? __('admin.mail_password_keep') : '' }}">
                            <div class="form-text">
                                {{ $hasPassword ? __('admin.mail_password_saved') : __('admin.mail_password_hint') }}
                            </div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if ($hasPassword)
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="mail-clear-password" name="clear_password"
                                           @checked(old('clear_password'))>
                                    <label class="form-check-label small text-muted" for="mail-clear-password">
                                        {{ __('admin.mail_password_clear') }}
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="pdj-card">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.mail_sender') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.mail_identity') }}</h2>
                        </div>
                        <span class="admin-stat-card__icon text-primary" style="width: 34px; height: 34px; margin: 0;">
                            <i class="bi bi-person-vcard"></i>
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="mail-from-address">{{ __('admin.mail_from_address') }}</label>
                            <input type="email" class="form-control @error('from_address') is-invalid @enderror"
                                   id="mail-from-address" name="from_address"
                                   value="{{ old('from_address', $fromAddress ?: $contactEmail) }}" required>
                            <div class="form-text">{{ __('admin.mail_from_address_hint') }}</div>
                            @error('from_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label" for="mail-from-name">{{ __('admin.mail_from_name') }}</label>
                            <input type="text" class="form-control @error('from_name') is-invalid @enderror"
                                   id="mail-from-name" name="from_name" value="{{ old('from_name', $fromName) }}"
                                   placeholder="{{ __('admin.mail_from_name_placeholder') }}">
                            @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="mail-notification-address">{{ __('admin.mail_notification_address') }}</label>
                            <input type="email" class="form-control @error('notification_address') is-invalid @enderror"
                                   id="mail-notification-address" name="notification_address"
                                   value="{{ old('notification_address', $notificationAddress) }}">
                            <div class="form-text">
                                {{ __('admin.mail_notification_hint', ['email' => $contactEmail]) }}
                            </div>
                            @error('notification_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.mail_test') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.mail_test_title') }}</h2>
                        </div>
                        <span class="admin-stat-card__icon text-primary" style="width: 34px; height: 34px; margin: 0;">
                            <i class="bi bi-send-check"></i>
                        </span>
                    </div>

                    <p class="small text-muted">
                        {{ __('admin.mail_test_hint') }}
                    </p>

                    <p class="small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ __('admin.mail_test_save_first') }}
                    </p>
                </section>

                <section class="pdj-card">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.mail_what_is_sent') }}</h2>

                    <ul class="list-unstyled small mb-0">
                        <li class="mb-3">
                            <i class="bi bi-envelope-check text-success me-1"></i>
                            <strong>{{ __('admin.mail_notify_client_title') }}</strong><br>
                            <span class="text-muted">{{ __('admin.mail_notify_client_text') }}</span>
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-building-check text-success me-1"></i>
                            <strong>{{ __('admin.mail_notify_agency_title') }}</strong><br>
                            <span class="text-muted">{{ __('admin.mail_notify_agency_text') }}</span>
                        </li>
                        <li>
                            <i class="bi bi-check2-circle text-success me-1"></i>
                            <strong>{{ __('admin.mail_notify_confirmed_title') }}</strong><br>
                            <span class="text-muted">{{ __('admin.mail_notify_confirmed_text') }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <div class="admin-settings-save">
            <a class="btn btn-light border" href="{{ route('admin.dashboard') }}">{{ __('admin.cancel') }}</a>
            <button class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>{{ __('admin.save_settings') }}
            </button>
        </div>
    </form>

    <section class="pdj-card mt-4">
        <h2 class="h6 fw-bold mb-3">{{ __('admin.mail_test_title') }}</h2>

        <form method="POST" action="{{ route('admin.mail.test') }}" class="row g-3 align-items-end">
            @csrf

            <div class="col-12 col-md-6">
                <label class="form-label" for="mail-test-email">{{ __('admin.mail_test_recipient') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="mail-test-email" name="email" value="{{ old('email') }}"
                       placeholder="{{ $notificationAddress }}">
                <div class="form-text">{{ __('admin.mail_test_recipient_hint') }}</div>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <button class="btn btn-outline-primary">
                    <i class="bi bi-send me-1"></i>{{ __('admin.mail_test_send') }}
                </button>
            </div>
        </form>
    </section>
@endsection
