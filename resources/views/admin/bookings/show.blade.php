@extends('layouts.panel')

@section('title', $booking->reference)

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 fw-bold mb-0">
        {{ $booking->reference }}
        <span class="badge bg-{{ $booking->status->color() }} ms-2">{{ $booking->status->label() }}</span>
    </h1>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="{{ route('booking.receipt', ['locale' => 'fr', 'booking' => $booking]) }}">
            {{ __('booking.download_receipt') }}
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.bookings.index') }}">
            {{ __('admin.back') }}
        </a>
    </div>
</div>

@if ($booking->status_reason)
    <div class="alert alert-warning">{{ $booking->status_reason }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">

        {{-- ── Actions ─────────────────────────────────────────────────────── --}}
        @if ($booking->status->value === 'en_attente')
            <div class="pdj-card mb-4" style="border-left: 4px solid var(--pdj-yellow);">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.decide') }}</h2>
                <p class="small text-muted">
                    {{ __('admin.decide_hint') }}
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST">
                        @csrf
                        <button class="btn btn-primary">{{ __('admin.confirm_and_notify') }}</button>
                    </form>
                    <form action="{{ route('admin.bookings.refuse', $booking) }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="text" class="form-control" name="motif" placeholder="{{ __('admin.reason') }}"
                               value="{{ $booking->status_reason }}" required>
                        <button class="btn btn-outline-danger">{{ __('admin.refuse') }}</button>
                    </form>
                </div>
            </div>
        @elseif ($booking->status->value === 'confirmee')
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.pickup_actions') }}</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ route('agent.pickup.documents', $booking) }}">
                        {{ __('admin.start_pickup') }}
                    </a>
                    <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST"
                          onsubmit="return confirm('{{ __('admin.confirm_cancel') }}')">
                        @csrf
                        <button class="btn btn-outline-danger">{{ __('admin.cancel_booking') }}</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── Client et conduite ─────────────────────────────────────────── --}}
        <div class="pdj-card mb-4">
            <h2 class="h6 fw-bold mb-3">{{ __('admin.client') }}</h2>
            <table class="table admin-table mb-0">
                <tbody>
                    @if ($booking->user)
                        <tr><td>{{ __('booking.field_first_name') }}</td><td class="text-end">{{ $booking->user->first_name }}</td></tr>
                        <tr><td>{{ __('booking.field_last_name') }}</td><td class="text-end">{{ $booking->user->last_name }}</td></tr>
                    @else
                        <tr><td>{{ __('booking.guest.name') }}</td><td class="text-end">{{ $booking->customer_name ?? '—' }}</td></tr>
                    @endif
                    <tr>
                        <td>{{ __('booking.field_phone') }}</td>
                        <td class="text-end">
                            @php($clientPhone = $booking->user?->phone ?? $booking->customer_phone)
                            @if ($clientPhone)
                                <a href="tel:{{ $clientPhone }}">{{ $clientPhone }}</a>
                            @else
                                —
                            @endif
                            @if ($booking->hasPhoneVerified())
                                <i class="bi bi-patch-check-fill text-success"></i>
                            @elseif ($booking->user)
                                <span class="badge bg-warning text-dark">{{ __('admin.phone_unverified') }}</span>
                            @endif
                        </td>
                    </tr>
                    @if ($booking->user)
                        <tr>
                            <td>{{ __('booking.field_email') }}</td>
                            <td class="text-end">{{ $booking->user->email }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('booking.field_birth_date') }}</td>
                            <td class="text-end">{{ $booking->user->birth_date?->format('d/m/Y') }} ({{ $booking->user->age() }} ans)</td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.absences') }}</td>
                            <td class="text-end">
                                @if ($booking->user->isBlocked())
                                    <span class="badge bg-danger">{{ __('admin.blocked') }}</span>
                                @else
                                    {{ $booking->user->no_show_count }}
                                @endif
                            </td>
                        </tr>
                    @else
                        <tr><td>{{ __('booking.field_email') }}</td><td class="text-end">{{ $booking->customer_email ?? '—' }}</td></tr>
                        @if ($booking->customer_comment)
                            <tr><td>{{ __('booking.guest.comment') }}</td><td class="text-end">{{ $booking->customer_comment }}</td></tr>
                        @endif
                    @endif
                </tbody>
            </table>
        </div>

        <div class="pdj-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">{{ __('admin.document_verification') }}</h2>
                <span class="badge bg-{{ $booking->document_verification_status->color() }}">
                    {{ $booking->document_verification_status->label() }}
                </span>
            </div>
            <p class="small text-muted">
                {{ __('admin.booking_status') }}: {{ $booking->status->label() }}.
                {{ __('admin.document_verification_independent') }}
            </p>
            <div class="small">
                {{ __('agent.identity_original_seen') }}:
                {{ $booking->identity_original_verified ? __('general.yes') : __('general.no') }}<br>
                {{ __('agent.identity_name_matches', ['name' => $booking->customer_name ?? $booking->user?->displayName() ?? '—']) }}:
                {{ $booking->identity_name_matches ? __('general.yes') : __('general.no') }}<br>
                {{ __('agent.age_requirement_met', ['age' => app(\App\Services\SettingsService::class)->int('booking.min_age')]) }}:
                {{ $booking->customer_age_eligible ? __('general.yes') : __('general.no') }}
                @unless ($booking->with_driver)
                    <br>{{ __('agent.license_original_seen') }}:
                    {{ $booking->driver_license_original_verified ? __('general.yes') : __('general.no') }}<br>
                    {{ __('agent.license_category') }}: {{ $booking->driver_license_category ?? '—' }}<br>
                    {{ __('agent.license_issued_at') }}: {{ $booking->driver_license_issued_at?->format('d/m/Y') ?? '—' }}<br>
                    {{ __('agent.license_expires_at') }}: {{ $booking->driver_license_expires_at?->format('d/m/Y') ?? '—' }}
                @endunless
            </div>
            @if ($booking->documents_verified_at)
                <div class="small text-muted mt-2">
                    {{ $booking->documents_verified_at->translatedFormat('d/m/Y H:i') }}
                    — {{ $booking->documentsVerifiedBy?->displayName() ?? '—' }}
                </div>
            @endif
            @if ($booking->document_verification_note)
                <div class="alert alert-warning small mt-3 mb-0">{{ $booking->document_verification_note }}</div>
            @endif
        </div>

        {{-- ── Documents ───────────────────────────────────────────────────── --}}
        <div class="pdj-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">{{ __('admin.documents') }}</h2>
                <a class="small" href="{{ route('admin.documents.index') }}">{{ __('admin.open_queue') }}</a>
            </div>

            @forelse ($booking->documents as $document)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <div class="small fw-semibold">{{ $document->type->label() }}</div>
                        <div class="small text-muted">
                            {{ $document->reviewed_at?->translatedFormat('d/m/Y H:i') ?? __('admin.not_reviewed') }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-{{ $document->status->color() }}">{{ $document->status->label() }}</span>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.documents.show', $document) }}">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">{{ __('admin.no_document') }}</p>
            @endforelse
        </div>

        {{-- ── Re-dater ───────────────────────────────────────────────────── --}}
        @unless ($booking->status->isFinal())
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.change_period') }}</h2>
                <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label" for="du">Départ</label>
                            <input type="date" class="form-control" id="du" name="du"
                                   value="{{ $booking->starts_at->toDateString() }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="heure_depart">Heure</label>
                            <input type="number" class="form-control" id="heure_depart" name="heure_depart"
                                   value="{{ $booking->pickup_hour }}" min="6" max="22" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="au">Retour</label>
                            <input type="date" class="form-control" id="au" name="au"
                                   value="{{ $booking->ends_at->toDateString() }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="heure_retour">Heure</label>
                            <input type="number" class="form-control" id="heure_retour" name="heure_retour"
                                   value="{{ $booking->return_hour }}" min="6" max="22" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="avec_chauffeur" value="1"
                                       id="avec_chauffeur" @checked($booking->with_driver)>
                                <label class="form-check-label" for="avec_chauffeur">{{ __('driver.badge') }}</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary mt-3">{{ __('admin.save_and_reprice') }}</button>
                </form>
            </div>

            @if ($booking->with_driver)
                <div class="pdj-card mb-4">
                    <h2 class="h6 fw-bold mb-3">{{ __('admin.assign_driver') }}</h2>
                    <p class="small text-muted">
                        @if ($booking->driver_daily_price !== null)
                            {{ $booking->days }} {{ __('general.days') }} ×
                            <strong>{{ number_format($booking->driver_daily_price, 0, ' ', ' ') }} FCFA</strong>
                        @else
                            {{ __('booking.driver_blocks', ['hours' => 4]) }} :
                            <strong>{{ $booking->driver_blocks }}</strong>
                        @endif
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse ($drivers as $driver)
                            <form action="{{ route('admin.bookings.driver.assign', [$booking, $driver]) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm {{ $booking->driver_id === $driver->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $driver->displayName() }}
                                    @if ($driver->rating) <span class="text-warning">★ {{ $driver->rating }}</span> @endif
                                </button>
                            </form>
                        @empty
                            <p class="text-muted small mb-0">{{ __('admin.no_driver') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif
        @endunless
    </div>

    {{-- ── Colonne prix ─────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="pdj-card mb-4">
            <h2 class="h6 fw-bold mb-3">{{ __('booking.cost_summary') }}</h2>
            <table class="table admin-table mb-0">
                <tbody>
                    <tr>
                        <td>{{ $booking->vehicle->displayName() }} · {{ $booking->days }} j</td>
                        <td class="text-end">{{ number_format($booking->price_rental, 0, ' ', ' ') }}</td>
                    </tr>
                    @if ($booking->with_driver)
                        <tr>
                            <td>
                                @if ($booking->driver_daily_price !== null)
                                    {{ __('driver.badge') }} ({{ $booking->days }} {{ __('general.days') }} × {{ number_format($booking->driver_daily_price, 0, ' ', ' ') }} FCFA)
                                @else
                                    {{ __('driver.badge') }} ({{ $booking->driver_blocks }} blocs)
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($booking->price_driver, 0, ' ', ' ') }}</td>
                        </tr>
                    @endif
                    <tr class="fw-bold">
                        <td>{{ __('general.pay_at_agency') }}</td>
                        <td class="text-end">{{ number_format($booking->price_total, 0, ' ', ' ') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('page.deposit_title') }}</td>
                        <td class="text-end">{{ number_format($booking->deposit_amount, 0, ' ', ' ') }}</td>
                    </tr>
                    <tr class="fw-bold" style="background: var(--pdj-green-light);">
                        <td>{{ __('general.carry_on_day') }}</td>
                        <td class="text-end">{{ number_format($booking->amountToCarry(), 0, ' ', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pdj-card">
            <h2 class="h6 fw-bold mb-3">{{ __('admin.payments') }}</h2>
            <table class="table admin-table mb-0">
                <tbody>
                    @forelse ($booking->payments as $payment)
                        <tr>
                            <td class="small">{{ $payment->kind->label() }}</td>
                            <td class="small text-muted">{{ $payment->method->label() }}</td>
                            <td class="text-end small">{{ number_format($payment->amount, 0, ' ', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted small">{{ __('admin.no_payment') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

            @unless ($booking->status->isFinal())
                <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('admin.payments.index') }}">
                    {{ __('admin.record_payment') }}
                </a>
            @endunless
        </div>
    </div>
</div>

@endsection
