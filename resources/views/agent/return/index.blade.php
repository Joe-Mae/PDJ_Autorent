@extends('layouts.panel', ['section' => 'agent'])

@section('title', 'Retours')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('agent.on_road') }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('agent.pickup.index') }}">
            <i class="bi bi-box-arrow-right me-1"></i>{{ __('agent.retreat') }}
        </a>
    </div>

    @if ($late->isNotEmpty())
        <div class="alert alert-danger">
            <h2 class="h6 fw-bold">{{ __('admin.overdue') }}</h2>
            @foreach ($late as $booking)
                <div class="small mb-1">
                    <a href="tel:{{ $booking->user?->phone }}">{{ $booking->user?->phone }}</a>
                    — {{ $booking->vehicle->displayName() }} · {{ $booking->ends_at->diffForHumans() }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        @forelse ($onRoad as $booking)
            @php($phone = $booking->user?->phone ?? $booking->customer_phone)
            <div class="col-md-6 col-lg-4">
                <div class="pdj-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-bold">{{ $booking->vehicle->displayName() }}</div>
                            <div class="small text-muted font-monospace">{{ $booking->vehicle->plate }}</div>
                        </div>
                        <span class="badge bg-primary">{{ __('agent.out') }}</span>
                    </div>

                    <div class="small text-muted mt-2">
                        <i class="bi bi-person me-1"></i>{{ $booking->user?->displayName() ?? $booking->customer_name }}<br>
                        @if ($phone)
                            <a class="text-muted" href="tel:{{ $phone }}">{{ $phone }}</a>
                        @endif
                    </div>

                    <div class="small mt-2">
                        <i class="bi bi-clock me-1"></i>
                        {{ __('agent.expected_return') }} {{ $booking->ends_at->translatedFormat('D d M H:i') }}
                    </div>

                    <a class="btn btn-primary w-100 mt-3" href="{{ route('agent.return.control', $booking) }}">
                        <i class="bi bi-box-arrow-in-left me-1"></i>{{ __('agent.return_control') }}
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="pdj-card text-center py-4">
                    <i class="bi bi-box-arrow-in-left fs-2 text-muted"></i>
                    <p class="text-muted mb-0 mt-2">{{ __('agent.no_vehicle_out') }}</p>
                    <a class="btn btn-sm btn-outline-primary mt-3" href="{{ route('admin.bookings.index') }}">
                        {{ __('admin.bookings') }}
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    @if ($upcoming->isNotEmpty())
        <h2 class="h5 fw-bold mt-4 mb-3">{{ __('agent.upcoming_returns') }}</h2>

        <div class="row g-3">
            @foreach ($upcoming as $booking)
                @php($phone = $booking->user?->phone ?? $booking->customer_phone)
                <div class="col-md-6 col-lg-4">
                    <div class="pdj-card h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-bold">{{ $booking->vehicle->displayName() }}</div>
                                <div class="small text-muted font-monospace">{{ $booking->vehicle->plate }}</div>
                            </div>
                            <span class="badge bg-secondary">{{ __('agent.not_out_yet') }}</span>
                        </div>

                        <div class="small text-muted mt-2">
                            <i class="bi bi-person me-1"></i>{{ $booking->user?->displayName() ?? $booking->customer_name }}<br>
                            @if ($phone)
                                <a class="text-muted" href="tel:{{ $phone }}">{{ $phone }}</a>
                            @endif
                        </div>

                        <div class="small mt-2">
                            <i class="bi bi-clock me-1"></i>
                            {{ __('agent.expected_return') }} {{ $booking->ends_at->translatedFormat('D d M H:i') }}
                        </div>

                        <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('admin.bookings.show', $booking) }}">
                            {{ __('admin.bookings') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
