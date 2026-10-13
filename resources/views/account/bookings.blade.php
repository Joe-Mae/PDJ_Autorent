@extends('layouts.app')

@section('title', __('account.my_bookings'))

@section('content')
    <div class="container py-4" style="max-width: 900px;">

    <x-account-bar />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">{{ __('account.my_bookings') }}</h1>
            <a class="btn btn-primary btn-sm" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
                {{ __('general.search_cta') }}
            </a>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('account.bookings', ['locale' => $locale]) }}">{{ __('admin.all') }}</a>
            @foreach ($statuses as $item)
                <a class="btn btn-sm {{ $status === $item->value ? 'btn-primary' : 'btn-outline-primary' }}"
                   href="{{ route('account.bookings', ['locale' => $locale, 'statut' => $item->value]) }}">
                    {{ $item->label() }}
                </a>
            @endforeach
        </div>

        @forelse ($bookings as $booking)
            <div class="pdj-card mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                        <div class="fw-bold mt-2">{{ $booking->vehicle->fullName() }}</div>
                        <div class="small text-muted">
                            {{ $booking->starts_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $booking->pickup_hour) }}
                            <i class="bi bi-arrow-right mx-1"></i>
                            {{ $booking->ends_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $booking->return_hour) }}
                        </div>
                        <div class="small text-muted font-monospace">{{ $booking->reference }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">{{ number_format($booking->price_total, 0, ' ', ' ') }} F CFA</div>
                        <div class="small text-muted">{{ __('general.pay_at_agency') }}</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('account.bookings.show', ['locale' => $locale, 'booking' => $booking]) }}">
                        {{ __('account.see_details') }}
                    </a>
                    <a class="btn btn-sm btn-outline-secondary"
                       href="{{ route('booking.receipt', ['locale' => $locale, 'booking' => $booking]) }}">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>

                    @if ($booking->status->canBeCancelled())
                        <form action="{{ route('account.bookings.cancel', ['locale' => $locale, 'booking' => $booking]) }}"
                              method="POST" onsubmit="return confirm('{{ __('booking.cancel_confirm') }}')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger">{{ __('booking.cancel_free') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="pdj-card text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="mb-3">{{ __('account.no_booking') }}</p>
                <a class="btn btn-primary" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
                    {{ __('general.search_cta') }}
                </a>
            </div>
        @endforelse

        <div class="mt-3">{{ $bookings->links() }}</div>
    </div>
@endsection
