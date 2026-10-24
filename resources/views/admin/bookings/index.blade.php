@extends('layouts.panel')

@section('title', __('admin.bookings'))
@section('breadcrumb', __('admin.bookings'))

@section('content')
    <x-admin.page-header
        :title="__('admin.bookings')"
        :subtitle="__('admin.bookings_count', ['count' => $bookings->total()])">
        <a class="btn btn-outline-primary" href="{{ route('admin.calendar') }}">
            <i class="bi bi-calendar3 me-1"></i>{{ __('admin.see_planning') }}
        </a>
        <a class="btn btn-primary" href="{{ route('agent.pickup.index') }}">
            <i class="bi bi-box-arrow-right me-1"></i>{{ __('admin.start_pickup') }}
        </a>
    </x-admin.page-header>

    {{-- Les trois prestations ne vivent pas dans la même table : ce sélecteur
         dit clairement où l'on se trouve, plutôt que de mélanger deux listes. --}}
    @if ($activeDriver)
        <div class="admin-info-strip mb-4">
            <i class="bi bi-person-badge"></i>
            <span>{{ __('admin.driver_history_for', ['name' => $activeDriver->displayName()]) }}</span>
            <a class="btn btn-sm btn-light border ms-auto" href="{{ route('admin.bookings.index') }}">
                {{ __('admin.reset_filters') }}
            </a>
        </div>
    @endif

    <ul class="nav nav-pills admin-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.bookings.index') }}" aria-current="page">
                <i class="bi bi-car-front me-1"></i>{{ __('admin.rentals_tab') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.driver-only-bookings.index') }}">
                <i class="bi bi-person-badge me-1"></i>{{ __('admin.driver_only_requests') }}
                @if ($driverOnlyPending > 0)
                    <span class="badge rounded-pill bg-warning text-dark ms-1">{{ $driverOnlyPending }}</span>
                @endif
            </a>
        </li>
    </ul>

    <form method="GET" class="pdj-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label" for="q">{{ __('admin.search') }}</label>
                <div class="admin-input-icon">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="{{ __('admin.booking_search_hint') }}">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="statut">{{ __('admin.status') }}</label>
                <select class="form-select" name="statut" id="statut">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}" @selected(($filters['statut'] ?? '') === $item->value)>
                            {{ $item->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="prestation">{{ __('admin.prestation') }}</label>
                <select class="form-select" name="prestation" id="prestation">
                    <option value="">{{ __('admin.all_prestations') }}</option>
                    <option value="sans_chauffeur" @selected(($filters['prestation'] ?? '') === 'sans_chauffeur')>
                        {{ __('admin.service_car_only') }}
                    </option>
                    <option value="avec_chauffeur" @selected(($filters['prestation'] ?? '') === 'avec_chauffeur')>
                        {{ __('admin.service_with_driver') }}
                    </option>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label" for="vehicule">{{ __('admin.vehicle') }}</label>
                <select class="form-select" name="vehicule" id="vehicule">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((string) ($filters['vehicule'] ?? '') === (string) $vehicle->id)>
                            {{ $vehicle->displayName() }} — {{ $vehicle->plate }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label" for="du">{{ __('admin.from') }}</label>
                <input type="date" class="form-control" id="du" name="du" value="{{ $filters['du'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label" for="au">{{ __('admin.to') }}</label>
                <input type="date" class="form-control" id="au" name="au" value="{{ $filters['au'] ?? '' }}">
            </div>
            <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                <a class="btn btn-light border" href="{{ route('admin.bookings.index') }}">
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
                        <th>{{ __('admin.reference') }}</th>
                        <th>{{ __('admin.client') }}</th>
                        <th>{{ __('admin.vehicle') }}</th>
                        <th>{{ __('admin.period') }}</th>
                        <th class="text-end">{{ __('admin.total') }}</th>
                        <th>{{ __('admin.payment') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        @php
                            $paid = (int) $booking->rental_paid;
                            $remaining = max(0, (int) $booking->price_total - $paid);
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">
                                    {{ $booking->reference }}
                                </a>
                                <div class="mt-1">
                                    <span class="admin-type-badge">
                                        <i class="bi bi-{{ $booking->with_driver ? 'person-badge' : 'car-front' }}"></i>
                                        {{ $booking->with_driver ? __('admin.service_with_driver') : __('admin.service_car_only') }}
                                    </span>
                                </div>
                            </td>
                            <td class="small">
                                <div class="fw-semibold">
                                    {{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}
                                </div>
                                @php($clientPhone = $booking->user?->phone ?? $booking->customer_phone)
                                @if ($clientPhone)
                                    <a class="text-muted text-decoration-none" href="tel:{{ $clientPhone }}">
                                        <i class="bi bi-telephone me-1"></i>{{ $clientPhone }}
                                    </a>
                                @endif
                            </td>
                            <td class="small">
                                <div>{{ $booking->vehicle->displayName() }}</div>
                                <span class="text-muted">{{ $booking->vehicle->plate }}</span>
                                @if ($booking->with_driver)
                                    <div class="text-muted">
                                        <i class="bi bi-person-badge me-1"></i>{{ $booking->driver?->displayName() ?? __('admin.no_driver') }}
                                    </div>
                                @endif
                            </td>
                            <td class="small text-nowrap">
                                {{ $booking->starts_at->translatedFormat('d/m/Y H:i') }}<br>
                                <span class="text-muted">→ {{ $booking->ends_at->translatedFormat('d/m/Y H:i') }}</span>
                            </td>
                            <td class="text-end text-nowrap fw-semibold">
                                {{ app(\App\Services\SettingsService::class)->money($booking->price_total) }}
                            </td>
                            <td class="small">
                                @if ($remaining === 0)
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                        <i class="bi bi-check2 me-1"></i>{{ __('admin.paid') }}
                                    </span>
                                @elseif ($paid > 0)
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        {{ __('admin.partially_paid') }}
                                    </span>
                                    <div class="text-muted mt-1">
                                        {{ __('admin.remaining_amount', ['amount' => app(\App\Services\SettingsService::class)->money($remaining)]) }}
                                    </div>
                                @else
                                    <span class="badge rounded-pill bg-light text-secondary border">
                                        {{ __('admin.to_collect') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($booking->isOverdue())
                                    <span class="badge rounded-pill bg-danger">{{ __('admin.overdue') }}</span>
                                @else
                                    <div class="small text-muted">{{ __('admin.booking_status') }}</div>
                                    <span class="badge rounded-pill bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                                @endif
                                <div class="small text-muted mt-1">{{ __('admin.document_verification') }}</div>
                                <span class="badge rounded-pill bg-{{ $booking->document_verification_status->color() }}">
                                    {{ $booking->document_verification_status->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-light border" href="{{ route('admin.bookings.show', $booking) }}"
                                   aria-label="{{ __('admin.view_details') }}" title="{{ __('admin.view_details') }}">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="admin-empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <span>{{ __('admin.no_booking') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
