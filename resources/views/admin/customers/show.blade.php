@extends('layouts.panel')

@section('title', $user->displayName())

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $user->displayName() }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.customers.index') }}">{{ __('admin.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.identity') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        <tr><td>{{ __('booking.field_phone') }}</td><td class="text-end"><a href="tel:{{ $user->phone }}">{{ $user->phone }}</a></td></tr>
                        <tr><td>{{ __('booking.field_email') }}</td><td class="text-end">{{ $user->email ?? 'â€”' }}</td></tr>
                        <tr><td>{{ __('booking.field_birth_date') }}</td><td class="text-end">{{ $user->birth_date?->format('d/m/Y') }} ({{ $user->age() }})</td></tr>
                        <tr><td>{{ __('admin.absences') }}</td><td class="text-end">{{ $user->no_show_count }}</td></tr>
                        <tr><td>{{ __('admin.active_bookings') }}</td><td class="text-end">{{ $user->activeBookingsCount() }}</td></tr>
                    </tbody>
                </table>
            </div>

            @if ($user->isBlocked())
                <div class="pdj-card mb-4" style="border-left: 4px solid var(--pdj-danger, #b3261e);">
                    <h2 class="h6 fw-bold mb-2">{{ __('admin.blocked') }}</h2>
                    <p class="small text-muted">{{ $user->block_reason }}</p>
                    <form method="POST" action="{{ route('admin.customers.unblock', $user) }}">
                        @csrf
                        <button class="btn btn-success w-100">{{ __('admin.unblock') }}</button>
                    </form>
                </div>
            @endif

            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-2">{{ __('admin.prepayment') }}</h2>
                <p class="small text-muted">{{ __('admin.prepayment_hint') }}</p>
                <form method="POST" action="{{ route('admin.customers.prepay', $user) }}">
                    @csrf
                    <input type="hidden" name="required" value="{{ $user->prepayment_required ? 0 : 1 }}">
                    <button class="btn btn-outline-{{ $user->prepayment_required ? 'secondary' : 'warning' }} w-100">
                        {{ $user->prepayment_required ? __('admin.prepay_off') : __('admin.prepay_on') }}
                    </button>
                </form>
            </div>

            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.documents') }}</h2>
                @forelse ($user->documents as $document)
                    <a class="d-block text-decoration-none text-reset border-bottom py-2"
                       href="{{ route('admin.documents.show', $document) }}">
                        <div class="small fw-semibold">{{ $document->type->label() }}</div>
                        <div class="small text-muted">
                            <span class="badge bg-{{ $document->status->color() }}">{{ $document->status->label() }}</span>
                        </div>
                    </a>
                @empty
                    <p class="text-muted small mb-0">{{ __('admin.no_document') }}</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-8">
            <div class="pdj-card p-0 overflow-hidden">
                <table class="table admin-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admin.reference') }}</th>
                            <th>{{ __('admin.period') }}</th>
                            <th>{{ __('admin.vehicle') }}</th>
                            <th class="text-end">{{ __('admin.total') }}</th>
                            <th>{{ __('admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                                <td class="small">{{ $booking->starts_at->translatedFormat('d/m/Y') }}</td>
                                <td class="small">{{ $booking->vehicle->displayName() }}</td>
                                <td class="text-end small">{{ number_format($booking->price_total, 0, ' ', ' ') }}</td>
                                <td><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('admin.no_booking') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $bookings->links() }}</div>
        </div>
    </div>
@endsection