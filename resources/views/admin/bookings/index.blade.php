@extends('layouts.panel')

@section('title', 'Réservations')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 fw-bold mb-0">{{ __('admin.bookings') }}</h1>
    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.calendar') }}">
        <i class="bi bi-calendar3 me-1"></i>{{ __('admin.see_planning') }}
    </a>
</div>

<form method="GET" class="pdj-card mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
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
        <div class="col-md-3">
            <label class="form-label" for="q">{{ __('admin.search') }}</label>
            <input type="search" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Référence, téléphone, nom">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="du">{{ __('admin.from') }}</label>
            <input type="date" class="form-control" id="du" name="du" value="{{ $filters['du'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="au">{{ __('admin.to') }}</label>
            <input type="date" class="form-control" id="au" name="au" value="{{ $filters['au'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">{{ __('admin.filter') }}</button>
        </div>
    </div>
</form>

<div class="pdj-card p-0 overflow-hidden">
    <table class="table admin-table mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('admin.reference') }}</th>
                <th>{{ __('admin.period') }}</th>
                <th>{{ __('admin.vehicle') }}</th>
                <th>{{ __('admin.client') }}</th>
                <th class="text-end">{{ __('admin.total') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td>
                        <a class="fw-semibold" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a>
                        @if ($booking->with_driver)
                            <i class="bi bi-person-badge ms-1" title="{{ __('driver.badge') }}"></i>
                        @endif
                    </td>
                    <td class="small">
                        {{ $booking->starts_at->translatedFormat('d/m H:i') }}<br>
                        <span class="text-muted">{{ $booking->ends_at->translatedFormat('d/m H:i') }}</span>
                    </td>
                    <td class="small">{{ $booking->vehicle->displayName() }}</td>
                    <td class="small">
                        {{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}<br>
                        @php($clientPhone = $booking->user?->phone ?? $booking->customer_phone)
                        @if ($clientPhone)
                            <a class="text-muted" href="tel:{{ $clientPhone }}">{{ $clientPhone }}</a>
                        @endif
                    </td>
                    <td class="text-end small fw-semibold">{{ number_format($booking->price_total, 0, ' ', ' ') }}</td>
                    <td><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.bookings.show', $booking) }}">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('admin.no_booking') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $bookings->links() }}</div>

@endsection
