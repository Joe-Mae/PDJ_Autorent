@extends('layouts.panel')

@section('title', $guest->customer_name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $guest->customer_name }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.customers.index') }}">{{ __('admin.back') }}</a>
    </div>

    <div class="pdj-card mb-4">
        <h2 class="h6 fw-bold mb-3">{{ __('admin.identity') }}</h2>
        <div class="d-flex flex-wrap gap-4">
            @if ($guest->customer_phone)
                <span>{{ __('booking.field_phone') }} : <a href="tel:{{ $guest->customer_phone }}">{{ $guest->customer_phone }}</a></span>
            @endif
            @if ($guest->customer_email)
                <span>{{ __('booking.field_email') }} : <a href="mailto:{{ $guest->customer_email }}">{{ $guest->customer_email }}</a></span>
            @endif
        </div>
    </div>

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
                        <td class="small">{{ $booking->starts_at->translatedFormat('d/m/Y') }} — {{ $booking->ends_at->translatedFormat('d/m/Y') }}</td>
                        <td class="small">{{ $booking->vehicle->displayName() }}</td>
                        <td class="text-end small">{{ number_format($booking->price_total, 0, ' ', ' ') }}</td>
                        <td>
                            @if ($booking->isOverdue())
                                <span class="badge bg-danger">{{ __('admin.overdue') }}</span>
                            @else
                                <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('admin.no_booking') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
