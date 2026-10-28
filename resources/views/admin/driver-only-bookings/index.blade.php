@extends('layouts.panel')

@section('title', __('driver.solo_admin_title'))
@section('breadcrumb', __('driver.solo_admin_title'))

@section('content')
    <x-admin.page-header
        :title="__('driver.solo_admin_title')"
        :subtitle="__('admin.driver_only_count', ['count' => $bookings->total()])">
        <a class="btn btn-outline-primary" href="{{ route('admin.bookings.index') }}">
            <i class="bi bi-car-front me-1"></i>{{ __('admin.rentals_tab') }}
        </a>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        @foreach ([
            [__('admin.pending_requests'), $counts['pending'], 'hourglass-split', 'warning'],
            [__('admin.confirmed_bookings'), $counts['confirmed'], 'check2-circle', 'success'],
            [__('admin.departures_today'), $counts['today'], 'box-arrow-up-right', 'primary'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-12 col-md-4">
                <div class="pdj-card admin-stat-card admin-stat-card--compact h-100">
                    <div class="admin-stat-card__icon text-{{ $tone }}">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                    <div class="admin-stat-card__label">{{ $label }}</div>
                    <div class="admin-stat-card__value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <ul class="nav nav-pills admin-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.bookings.index') }}">
                <i class="bi bi-car-front me-1"></i>{{ __('admin.rentals_tab') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.driver-only-bookings.index') }}" aria-current="page">
                <i class="bi bi-person-badge me-1"></i>{{ __('admin.driver_only_requests') }}
            </a>
        </li>
    </ul>

    <form method="GET" class="pdj-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="q">{{ __('admin.search') }}</label>
                <div class="admin-input-icon">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}"
                           placeholder="{{ __('admin.driver_only_search_hint') }}">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="statut">{{ __('admin.status') }}</label>
                <select class="form-select" name="statut" id="statut">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label" for="chauffeur">{{ __('admin.driver') }}</label>
                <select class="form-select" name="chauffeur" id="chauffeur">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($allDrivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) request('chauffeur') === (string) $driver->id)>
                            {{ $driver->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex justify-content-end gap-2">
                <a class="btn btn-light border" href="{{ route('admin.driver-only-bookings.index') }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.reset_filters') }}
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('admin.filter') }}
                </button>
            </div>
        </div>
    </form>

    <div class="pdj-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('driver.solo_reference') }}</th>
                        <th>{{ __('driver.solo_customer') }}</th>
                        <th>{{ __('driver.solo_period') }}</th>
                        <th class="text-end">{{ __('driver.solo_total') }}</th>
                        <th>{{ __('driver.solo_driver') }}</th>
                        <th>{{ __('driver.solo_status_label') }}</th>
                        <th class="text-end">{{ __('driver.solo_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $booking->reference }}</td>
                            <td class="small">
                                <div class="fw-semibold">{{ $booking->customer_name }}</div>
                                @if ($booking->phone)
                                    <a class="text-muted text-decoration-none" href="tel:{{ preg_replace('/\D/', '', $booking->phone) }}">
                                        <i class="bi bi-telephone me-1"></i>{{ $booking->phone }}
                                    </a>
                                @endif
                            </td>
                            <td class="small text-nowrap">
                                {{ $booking->starts_at->translatedFormat('d/m/Y') }} →
                                {{ $booking->ends_at->translatedFormat('d/m/Y') }}
                                <div class="text-muted">
                                    {{ $booking->days }} {{ __('general.days') }} ×
                                    {{ app(\App\Services\SettingsService::class)->money($booking->daily_price) }}
                                </div>
                            </td>
                            <td class="text-end text-nowrap fw-semibold">
                                {{ app(\App\Services\SettingsService::class)->money($booking->price_total) }}
                            </td>
                            <td class="small">
                                @if ($booking->driver)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="admin-avatar admin-avatar--sm">{{ $booking->driver->initials() }}</span>
                                        {{ $booking->driver->displayName() }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $booking->status->color() }}">
                                    {{ $booking->status->label() }}
                                </span>
                                @if ($booking->status_reason)
                                    <div class="small text-muted mt-1">{{ $booking->status_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    @if ($booking->status === \App\Enums\DriverOnlyBookingStatus::EnAttente)
                                        <form method="POST" action="{{ route('admin.driver-only-bookings.confirm', $booking) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">{{ __('driver.solo_confirm') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.driver-only-bookings.refuse', $booking) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">{{ __('driver.solo_refuse') }}</button>
                                        </form>
                                    @elseif ($booking->status === \App\Enums\DriverOnlyBookingStatus::Confirmee)
                                        <form method="POST" action="{{ route('admin.driver-only-bookings.assign', $booking) }}"
                                              class="d-flex flex-wrap justify-content-end gap-1">
                                            @csrf
                                            <select class="form-select form-select-sm admin-status-select" name="driver" required>
                                                @foreach ($drivers as $driver)
                                                    <option value="{{ $driver->id }}" @selected($booking->driver_id === $driver->id)>
                                                        {{ $driver->displayName() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($drivers->isNotEmpty())
                                                <button class="btn btn-sm btn-primary">{{ __('driver.solo_assign') }}</button>
                                            @else
                                                <span class="small text-muted">{{ __('admin.no_driver') }}</span>
                                            @endif
                                        </form>
                                        <form method="POST" action="{{ route('admin.driver-only-bookings.start', $booking) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary">{{ __('driver.solo_start') }}</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger solo-cancel"
                                                data-bs-toggle="modal" data-bs-target="#soloCancelModal"
                                                data-cancel-url="{{ route('admin.driver-only-bookings.cancel', $booking) }}"
                                                data-reference="{{ $booking->reference }}">
                                            {{ __('driver.solo_cancel') }}
                                        </button>
                                    @elseif ($booking->status === \App\Enums\DriverOnlyBookingStatus::EnCours)
                                        <form method="POST" action="{{ route('admin.driver-only-bookings.finish', $booking) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">{{ __('driver.solo_finish') }}</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger solo-cancel"
                                                data-bs-toggle="modal" data-bs-target="#soloCancelModal"
                                                data-cancel-url="{{ route('admin.driver-only-bookings.cancel', $booking) }}"
                                                data-reference="{{ $booking->reference }}">
                                            {{ __('driver.solo_cancel') }}
                                        </button>
                                    @else
                                        <span class="small text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="admin-empty-state">
                                    <i class="bi bi-person-badge"></i>
                                    <span>{{ __('driver.solo_empty') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bookings->links() }}</div>

    {{-- Un seul formulaire d'annulation partagé : le bouton de la ligne lui
         transmet l'URL de la demande et sa référence, puis le motif est saisi
         au calme dans une fenêtre plutôt que dans une cellule de tableau. --}}
    <div class="modal fade" id="soloCancelModal" tabindex="-1" aria-labelledby="soloCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="soloCancelForm"
                      data-intro="{{ __('driver.solo_cancel_intro', ['ref' => '__REF__']) }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="soloCancelModalLabel">{{ __('driver.solo_cancel_title') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted" id="soloCancelIntro">{{ __('driver.solo_cancel_intro', ['ref' => '']) }}</p>
                        <label class="form-label" for="solo-cancel-motif">{{ __('admin.reason') }}</label>
                        <input type="text" class="form-control" id="solo-cancel-motif" name="motif"
                               required maxlength="255" placeholder="{{ __('driver.solo_cancel_reason_hint') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            {{ __('admin.back') }}
                        </button>
                        <button class="btn btn-danger">{{ __('driver.solo_cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Chaque bouton « Annuler » porte sa propre URL : le formulaire de
            // la fenêtre est réutilisé, jamais dupliqué par ligne.
            document.querySelectorAll('.solo-cancel').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('soloCancelForm');
                    const intro = document.getElementById('soloCancelIntro');

                    form.action = button.dataset.cancelUrl;
                    form.reset();
                    intro.textContent = form.dataset.intro.replace('__REF__', button.dataset.reference);
                });
            });
        </script>
    @endpush
@endsection
