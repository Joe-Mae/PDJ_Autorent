@extends('layouts.panel', ['section' => 'agent'])

@section('title', 'Retraits')

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pdj-card mb-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-upc-scan me-2"></i>{{ __('agent.scan_or_type') }}</h2>
                <form method="POST" action="{{ route('agent.pickup.pick') }}">
                    @csrf
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control font-monospace text-uppercase" name="reference"
                               placeholder="PDJ-XXXXXX" maxlength="12" required autofocus>
                        <button class="btn btn-primary">{{ __('agent.open') }}</button>
                    </div>
                </form>
                <p class="small text-muted mt-2 mb-0">{{ __('agent.scan_hint') }}</p>
            </div>

            @if ($late->isNotEmpty())
                <div class="alert alert-danger">
                    <h3 class="h6 fw-bold">{{ __('admin.overdue') }}</h3>
                    @foreach ($late as $booking)
                        <div class="small mb-1">
                            @php($clientPhone = $booking->user?->phone ?? $booking->customer_phone)
                            <a href="tel:{{ $clientPhone }}">{{ $clientPhone }}</a>
                            — {{ $booking->reference }} ({{ $booking->starts_at->diffForHumans() }})
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">
                    {{ __('admin.departures_today') }}
                    <span class="badge bg-primary ms-1">{{ $expected->count() }}</span>
                </h2>

                @forelse ($expected as $booking)
                    <a class="d-block text-decoration-none text-reset border-bottom py-2"
                       href="{{ route('agent.pickup.documents', $booking) }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ sprintf('%02dh00', $booking->pickup_hour) }} — {{ $booking->vehicle->displayName() }}</div>
                                <div class="small text-muted">
                                    {{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}
                                    — {{ $booking->user?->phone ?? $booking->customer_phone }}
                                </div>
                                <div class="small text-muted">
                                    {{ $booking->driver_id?->displayName() ?? ($booking->with_driver ? __('agent.driver_to_assign') : '—') }}
                                </div>
                                <div class="small mt-1">
                                    <span class="badge rounded-pill bg-{{ $booking->document_verification_status->color() }}">
                                        {{ $booking->document_verification_status->label() }}
                                    </span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </a>
                @empty
                    <p class="text-muted small mb-0">{{ __('admin.no_departure_today') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection