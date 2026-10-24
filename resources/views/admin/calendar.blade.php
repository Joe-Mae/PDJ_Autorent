@extends('layouts.panel')

@section('title', __('admin.calendar'))
@section('breadcrumb', __('admin.calendar'))

@section('content')
    <x-admin.page-header
        :eyebrow="__('admin.calendar')"
        :title="$month->translatedFormat('F Y')"
        :subtitle="__('admin.calendar_subtitle')">
        <div class="btn-group" role="group" aria-label="{{ __('admin.calendar_navigation') }}">
            <a class="btn btn-light border" href="{{ route('admin.calendar', ['mois' => $month->subMonth()->format('Y-m')]) }}"
               aria-label="{{ __('admin.previous_month') }}">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a class="btn btn-light border" href="{{ route('admin.calendar', ['mois' => now()->format('Y-m')]) }}">
                {{ __('admin.this_month') }}
            </a>
            <a class="btn btn-light border" href="{{ route('admin.calendar', ['mois' => $month->addMonth()->format('Y-m')]) }}"
               aria-label="{{ __('admin.next_month') }}">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </x-admin.page-header>

    <div class="admin-legend mb-3">
        <span class="admin-legend__item"><span class="admin-legend__dot admin-legend__dot--free"></span>{{ __('admin.available') }}</span>
        @foreach (array_filter(\App\Enums\BookingStatus::cases(), fn ($status) => $status->isActive()) as $status)
            <span class="admin-legend__item">
                <span class="admin-legend__dot bg-{{ $status->color() }}"></span>{{ $status->label() }}
            </span>
        @endforeach
        <span class="admin-legend__item"><span class="admin-legend__dot bg-danger"></span>{{ __('admin.overdue') }}</span>
        <span class="admin-legend__item"><span class="admin-legend__dot bg-secondary"></span>{{ __('admin.blocked_slot') }}</span>
    </div>

    <div class="pdj-card p-0 overflow-auto admin-calendar-wrap">
        <table class="table admin-table admin-calendar mb-0">
            <thead class="table-light">
                <tr>
                    <th class="admin-calendar__vehicle">{{ __('admin.vehicle') }}</th>
                    @for ($day = 1; $day <= $days; $day++)
                        @php
                            $date = $month->day($day);
                        @endphp
                        <th class="text-center p-1 {{ $date->isToday() ? 'is-today' : ($date->isWeekend() ? 'is-weekend' : '') }}"
                            title="{{ $date->translatedFormat('l d F Y') }}">
                            {{ $day }}
                            <span class="d-block admin-calendar__weekday">{{ $date->translatedFormat('D') }}</span>
                        </th>
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
                            <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $vehicle->plate }}</div>
                            <span class="badge bg-{{ $vehicle->status->color() }}" style="font-size: 0.65rem;">
                                {{ $vehicle->status->label() }}
                            </span>
                        </td>
                        @for ($day = 1; $day <= $days; $day++)
                            @php
                                $date = $month->day($day)->startOfDay();
                                $dayStart = $date->startOfDay();
                                $dayEnd = $date->endOfDay();
                                $dayBlocks = $blocks->filter(fn ($b) => $b->vehicle_id === $vehicle->id && $b->starts_at <= $dayEnd && $b->ends_at >= $dayStart);
                                $dayBookings = $bookings->filter(fn ($b) => $b->vehicle_id === $vehicle->id && $b->starts_at <= $dayEnd && $b->ends_at >= $dayStart);
                            @endphp
                            <td class="text-center p-1 {{ $date->isToday() ? 'is-today' : ($date->isWeekend() ? 'is-weekend' : '') }}">
                                @foreach ($dayBlocks as $block)
                                    <span class="badge bg-secondary d-block" style="font-size: 0.6rem;"
                                          title="{{ $block->reason->label() }} {{ $block->note }}">{{ $block->reason->label() }}</span>
                                @endforeach
                                @foreach ($dayBookings as $booking)
                                    @php($clientName = $booking->user?->displayName() ?? $booking->customer_name ?? '?')
                                    <span class="badge bg-{{ $booking->isOverdue() ? 'danger' : $booking->status->color() }} d-block" style="font-size: 0.6rem;"
                                          title="{{ $clientName }} — {{ $booking->isOverdue() ? __('admin.overdue') : $booking->status->label() }}">
                                        {{ mb_substr($booking->user?->first_name ?? $booking->customer_name ?? '?', 0, 1) }}
                                    </span>
                                @endforeach
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $days + 1 }}">
                            <div class="admin-empty-state">
                                <i class="bi bi-car-front"></i>
                                <span>{{ __('admin.no_vehicle') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3">
        <i class="bi bi-info-circle me-1"></i>{{ __('admin.calendar_hint') }}
    </p>
@endsection
