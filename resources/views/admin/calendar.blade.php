@extends('layouts.panel')

@section('title', 'Planning de disponibilitÃ©')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.calendar') }} â€” {{ $month->translatedFormat('F Y') }}</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.calendar', ['mois' => $month->subMonth()->format('Y-m')]) }}">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.calendar', ['mois' => now()->format('Y-m')]) }}">{{ __('admin.this_month') }}</a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.calendar', ['mois' => $month->addMonth()->format('Y-m')]) }}">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="pdj-card p-0 overflow-auto">
        <table class="table admin-table mb-0" style="min-width: 900px;">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 190px;">{{ __('admin.vehicle') }}</th>
                    @for ($day = 1; $day <= $days; $day++)
                        <th class="text-center p-1 {{ $month->day($day)->isToday() ? 'table-warning' : '' }}">{{ $day }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td class="small">
                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.calendar.vehicle', $vehicle) }}?mois={{ $month->format('Y-m') }}">
                                {{ $vehicle->displayName() }}
                            </a>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $vehicle->plate }}</div>
                        </td>
                        @for ($day = 1; $day <= $days; $day++)
                            @php
                                $date = $month->day($day)->startOfDay();
                                $dayBlocks = $blocks->filter(fn ($b) => $b->vehicle_id === $vehicle->id && $b->starts_at->isSameDay($date));
                                $dayBookings = $bookings->filter(fn ($b) => $b->vehicle_id === $vehicle->id && $b->starts_at->isSameDay($date));
                            @endphp
                            <td class="text-center p-1" style="min-width: 30px;">
                                @foreach ($dayBlocks as $block)
                                    <span class="badge bg-secondary d-block" style="font-size: 0.6rem;"
                                          title="{{ $block->reason->label() }} {{ $block->note }}">{{ $block->reason->label() }}</span>
                                @endforeach
                                @foreach ($dayBookings as $booking)
                                    <span class="badge bg-{{ $booking->status->color() }} d-block" style="font-size: 0.6rem;"
                                          title="{{ $booking->user?->displayName() }} â€” {{ $booking->status->label() }}">{{ $booking->user?->initials() }}</span>
                                @endforeach
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-4">{{ __('admin.no_vehicle') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-2">
        {{ __('admin.calendar_hint') }}
    </p>
@endsection