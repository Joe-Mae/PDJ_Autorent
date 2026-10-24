@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Remise des clés')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 6/6</div>
                <div class="d-grid mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; background: var(--pdj-green-light); place-items: center;">
                    <i class="bi bi-key fs-2" style="color: var(--pdj-green);"></i>
                </div>
                <h1 class="h4 fw-bold">{{ __('agent.handover') }}</h1>
                <p class="text-muted">{{ $booking->reference }}</p>
            </div>

            <div class="pdj-card mb-3">
                <table class="table admin-table mb-0">
                    <tbody>
                        <tr><td>{{ __('admin.vehicle') }}</td><td class="text-end fw-semibold">{{ $booking->vehicle->fullName() }}</td></tr>
                        <tr><td>{{ __('admin.plate') }}</td><td class="text-end font-monospace">{{ $booking->vehicle->plate }}</td></tr>
                        <tr><td>{{ __('admin.client') }}</td><td class="text-end">{{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}</td></tr>
                        <tr><td>{{ __('agent.mileage_km') }}</td><td class="text-end">{{ number_format($booking->mileage_at_pickup ?? 0, 0, ' ', ' ') }} km</td></tr>
                        <tr><td>{{ __('agent.fuel_level') }}</td><td class="text-end">{{ $booking->fuel_level_pickup ? __('agent.fuel_'.$booking->fuel_level_pickup) : '—' }}</td></tr>
                    </tbody>
                </table>
            </div>

            @if ($handoverReady)
                <div class="alert alert-success small">
                    <i class="bi bi-check-circle me-1"></i>{{ __('agent.before_keys') }}
                </div>
            @else
                <div class="alert alert-danger small">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ __('agent.handover_blocked') }}
                </div>
            @endif

            @if ($handoverReady)
                <form method="POST" action="{{ route('agent.pickup.complete', $booking) }}">
                    @csrf
                    <button class="btn btn-primary btn-lg w-100">{{ __('agent.give_keys') }}</button>
                </form>
            @endif
            <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
        </div>
    </div>
@endsection