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
                    â€” {{ $booking->vehicle->displayName() }} w {{ $booking->ends_at->diffForHumans() }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        @forelse ($onRoad as $booking)
            <div class="col-md-6 col-lg-4">
                <a class="d-block text-decoration-none text-reset pdj-card h-100" href="{{ route('agent.return.control', $booking) }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold">{{ $booking->vehicle->displayName() }}</div>
                            <div class="small text-muted font-monospace">{{ $booking->vehicle->plate }}</div>
                        </div>
                        <span class="badge bg-primary">{{ __('agent.out') }}</span>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-person me-1"></i>{{ $booking->user?->displayName() }}<br>
                        <a class="text-muted" href="tel:{{ $booking->user?->phone }}">{{ $booking->user?->phone }}</a>
                    </div>
                    <div class="small mt-2">
                        <i class="bi bi-clock me-1"></i>
                        Retour attendu {{ $booking->ends_at->translatedFormat('D d M H:i') }}
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">{{ __('agent.no_vehicle_out') }}</p>
            </div>
        @endforelse
    </div>
@endsection