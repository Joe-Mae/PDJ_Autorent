@extends('layouts.panel')

@section('title', __('driver.solo_admin_title'))

@section('content')
    <h1 class="h4 fw-bold mb-3">{{ __('driver.solo_admin_title') }}</h1>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>{{ __('driver.solo_reference') }}</th>
                    <th>{{ __('driver.solo_customer') }}</th>
                    <th>{{ __('driver.solo_period') }}</th>
                    <th>{{ __('driver.solo_total') }}</th>
                    <th>{{ __('driver.solo_status_label') }}</th>
                    <th>{{ __('driver.solo_driver') }}</th>
                    <th>{{ __('driver.solo_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->reference }}</td>
                        <td>
                            <div>{{ $booking->customer_name }}</div>
                            <a href="tel:{{ preg_replace('/\D/', '', $booking->phone) }}">{{ $booking->phone }}</a>
                        </td>
                        <td>
                            {{ $booking->starts_at->format('d/m/Y') }} –
                            {{ $booking->ends_at->format('d/m/Y') }}
                            <div class="small text-muted">
                                {{ $booking->days }} {{ __('general.days') }} ×
                                {{ number_format($booking->daily_price, 0, ' ', ' ') }} F CFA
                            </div>
                        </td>
                        <td>{{ number_format($booking->price_total, 0, ' ', ' ') }} F CFA</td>
                        <td>
                            <span class="badge text-bg-{{ $booking->status === \App\Enums\DriverOnlyBookingStatus::EnAttente ? 'warning' : ($booking->status === \App\Enums\DriverOnlyBookingStatus::Confirmee ? 'success' : 'secondary') }}">
                                {{ $booking->status->label() }}
                            </span>
                        </td>
                        <td>{{ $booking->driver?->displayName() ?? '—' }}</td>
                        <td>
                            @if ($booking->status === \App\Enums\DriverOnlyBookingStatus::EnAttente)
                                <div class="d-flex flex-wrap gap-1">
                                    <form method="POST" action="{{ route('admin.driver-only-bookings.confirm', $booking) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">{{ __('driver.solo_confirm') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.driver-only-bookings.refuse', $booking) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">{{ __('driver.solo_refuse') }}</button>
                                    </form>
                                </div>
                            @elseif ($booking->status === \App\Enums\DriverOnlyBookingStatus::Confirmee)
                                <form method="POST" action="{{ route('admin.driver-only-bookings.assign', $booking) }}">
                                    @csrf
                                    <select class="form-select form-select-sm mb-1" name="driver" required>
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
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('driver.solo_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $bookings->links() }}
@endsection
