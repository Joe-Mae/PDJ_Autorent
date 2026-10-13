@extends('layouts.panel')

@section('title', 'Tableau de bord')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold mb-0">{{ __('admin.dashboard_title') }}</h1>
    <a class="btn btn-primary" href="{{ route('agent.pickup.index') }}">
        <i class="bi bi-box-arrow-right me-1"></i>{{ __('admin.start_pickup') }}
    </a>
</div>

{{-- ── Chiffres du jour ───────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @foreach ([
        ['Véhicules disponibles', $stats['vehicules'], 'car-front', 'text-success'],
        ['En location', $stats['en_location'], 'key', 'text-primary'],
        ['En maintenance', $stats['maintenance'], 'tools', 'text-warning'],
        ['Chauffeurs libres', $stats['chauffeurs'], 'person-badge', 'text-secondary'],
    ] as [$label, $value, $icon, $color])
        <div class="col-6 col-lg-3">
            <div class="pdj-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small text-muted">{{ $label }}</div>
                        <div class="h3 fw-bold mb-0">{{ $value }}</div>
                    </div>
                    <i class="bi bi-{{ $icon }} fs-4 {{ $color }}"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">

    {{-- ── Ce qui demande une action ─────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="pdj-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">
                    <i class="bi bi-hourglass-split me-1" style="color: var(--pdj-green);"></i>
                    {{ __('admin.to_validate') }}
                </h2>
                <span class="badge bg-warning text-dark">{{ $pending['validation']->count() }}</span>
            </div>

            @forelse ($pending['validation'] as $booking)
                <div class="border-bottom py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <a class="fw-semibold text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">
                            {{ $booking->reference }}
                        </a>
                        <span class="text-muted small ms-2">{{ $booking->vehicle->displayName() }}</span>
                        <div class="small text-muted">
                            {{ $booking->user?->displayName() }} · {{ $booking->user?->phone }} ·
                            {{ $booking->starts_at->translatedFormat('D d M H:i') }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.documents.index') }}">
                            {{ __('admin.check_documents') }}
                        </a>
                        <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.confirm') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">{{ __('admin.nothing_to_validate') }}</p>
            @endforelse
        </div>

        <div class="pdj-card mb-4">
            <h2 class="h6 fw-bold mb-2">
                <i class="bi bi-calendar-event me-1" style="color: var(--pdj-green);"></i>
                {{ __('admin.departures_today') }}
            </h2>
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.hour') }}</th>
                        <th>{{ __('admin.reference') }}</th>
                        <th>{{ __('admin.vehicle') }}</th>
                        <th>{{ __('admin.client') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($today['departures'] as $booking)
                        <tr>
                            <td>{{ sprintf('%02dh00', $booking->pickup_hour) }}</td>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                            <td>{{ $booking->vehicle->displayName() }}</td>
                            <td>{{ $booking->user?->displayName() }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('agent.pickup.documents', $booking) }}">
                                    {{ __('admin.pickup') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted small">{{ __('admin.no_departure_today') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Colonne latérale ───────────────────────────────────────────────── --}}
    <div class="col-lg-4">
        @if ($today['overdue']->isNotEmpty())
            <div class="alert alert-danger">
                <h2 class="h6 fw-bold">{{ __('admin.overdue') }}</h2>
                @foreach ($today['overdue'] as $booking)
                    <div class="small mb-1">
                        <a href="tel:{{ $booking->user?->phone }}">{{ $booking->reference }}</a>
                        — {{ $booking->vehicle->displayName() }} ·
                        {{ $booking->user?->phone }}
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pdj-card mb-4">
            <h2 class="h6 fw-bold mb-3">{{ __('admin.cash_today') }}</h2>
            <table class="table admin-table mb-0">
                <tbody>
                    <tr><td>{{ __('admin.rentals') }}</td><td class="text-end">{{ number_format($cash['today_in'], 0, ' ', ' ') }}</td></tr>
                    <tr><td>{{ __('admin.refunds') }}</td><td class="text-end">− {{ number_format($cash['today_out'], 0, ' ', ' ') }}</td></tr>
                    <tr class="fw-bold">
                        <td>{{ __('admin.deposits_held') }}</td>
                        <td class="text-end">{{ number_format($cash['deposits_held'], 0, ' ', ' ') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('admin.month') }}</td>
                        <td class="text-end">{{ number_format($monthRevenue, 0, ' ', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
            <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('agent.cash.index') }}">
                {{ __('admin.open_cash') }}
            </a>
        </div>

        <div class="pdj-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">{{ __('admin.documents_queue') }}</h2>
                <a class="small" href="{{ route('admin.documents.index') }}">{{ __('general.see_all') }}</a>
            </div>

            @forelse ($pending['documents'] as $document)
                <a class="d-block text-decoration-none text-reset border-bottom py-2"
                   href="{{ route('admin.documents.show', $document) }}">
                    <div class="small fw-semibold">{{ $document->type->label() }}</div>
                    <div class="small text-muted">
                        {{ $document->user?->displayName() }}
                        @if ($document->booking) — {{ $document->booking->reference }} @endif
                    </div>
                </a>
            @empty
                <p class="text-muted small mb-0">{{ __('admin.no_document') }}</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
