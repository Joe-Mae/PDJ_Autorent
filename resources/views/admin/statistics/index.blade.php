@extends('layouts.panel')

@section('title', __('admin.statistics'))
@section('breadcrumb', __('admin.statistics'))

@section('content')
    @php
        $totalBookings = array_sum($statusCounts->all());
        $maxStatus = max(1, (int) $statusCounts->max());
        $serviceTotal = max(1, array_sum($serviceBreakdown));
        $services = [
            ['label' => __('admin.service_car_only'), 'count' => $serviceBreakdown['car_only'], 'tone' => 'primary'],
            ['label' => __('admin.service_with_driver'), 'count' => $serviceBreakdown['with_driver'], 'tone' => 'success'],
            ['label' => __('admin.service_driver_only'), 'count' => $serviceBreakdown['driver_only'], 'tone' => 'warning'],
        ];
    @endphp

    <x-admin.page-header
        :eyebrow="$range['from']->translatedFormat('d/m/Y').' — '.$range['to']->translatedFormat('d/m/Y')"
        :title="__('admin.statistics')"
        :subtitle="__('admin.statistics_subtitle')">
        <form method="GET" action="{{ route('admin.statistics.index') }}" class="d-flex align-items-center gap-2">
            <label class="visually-hidden" for="periode">{{ __('admin.period') }}</label>
            <select class="form-select form-select-sm admin-period-select" name="periode" id="periode" onchange="this.form.submit()">
                @foreach (['semaine' => __('admin.this_week'), 'mois' => __('admin.this_month'), 'annee' => __('admin.this_year')] as $value => $label)
                    <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        @foreach ([
            [__('admin.bookings'), number_format($totalBookings, 0, ',', ' '), 'calendar-check', 'primary'],
            [__('admin.completed_rentals'), number_format($completedCount, 0, ',', ' '), 'check-circle', 'success'],
            [__('admin.overdue_rentals'), number_format($overdueCount, 0, ',', ' '), 'exclamation-triangle', 'danger'],
            [__('admin.actual_rental_revenue'), number_format($actualRentalRevenue, 0, ',', ' ').' F', 'cash-stack', 'success'],
            [__('admin.outstanding'), number_format($outstanding, 0, ',', ' ').' F', 'hourglass-bottom', 'warning'],
            [__('admin.fleet_utilization'), $utilization.' %', 'speedometer2', 'info'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pdj-card admin-stat-card admin-stat-card--compact h-100">
                    <div class="admin-stat-card__icon text-{{ $tone }}">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                    <div class="admin-stat-card__label">{{ $label }}</div>
                    <div class="admin-stat-card__value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-5">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.over_period') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.service_breakdown') }}</h2>
                    </div>
                </div>

                <div class="admin-breakdown">
                    @foreach ($services as $service)
                        <div class="admin-breakdown__row">
                            <div class="admin-breakdown__head">
                                <span>{{ $service['label'] }}</span>
                                <strong>{{ $service['count'] }}</strong>
                            </div>
                            <div class="admin-breakdown__track" role="presentation">
                                <div class="admin-breakdown__fill admin-breakdown__fill--{{ $service['tone'] }}"
                                     style="width: {{ round($service['count'] / $serviceTotal * 100) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="admin-cash-row mt-3">
                    <span>{{ __('admin.actual_rental_revenue') }}</span>
                    <strong class="text-success">{{ number_format($actualRentalRevenue, 0, ',', ' ') }} F CFA</strong>
                </div>
                <div class="admin-cash-row">
                    <span>{{ __('admin.outstanding') }}</span>
                    <strong class="{{ $outstanding > 0 ? 'text-danger' : '' }}">{{ number_format($outstanding, 0, ',', ' ') }} F CFA</strong>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-7">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.over_period') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.bookings_by_period') }}</h2>
                    </div>
                    <span class="badge rounded-pill text-bg-light border">{{ $totalBookings }}</span>
                </div>

                <div class="admin-status-breakdown">
                    @foreach (\App\Enums\BookingStatus::cases() as $status)
                        @php($count = (int) ($statusCounts[$status->value] ?? 0))
                        <div class="admin-status-breakdown__row">
                            <span class="badge rounded-pill bg-{{ $status->color() }}">{{ $status->label() }}</span>
                            <div class="admin-breakdown__track" role="presentation">
                                <div class="admin-breakdown__fill admin-breakdown__fill--primary"
                                     style="width: {{ round($count / $maxStatus * 100) }}%;"></div>
                            </div>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.over_period') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.top_rented_vehicles') }}</h2>
                    </div>
                    <i class="bi bi-car-front text-primary fs-4"></i>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin.vehicle') }}</th>
                                <th class="text-end">{{ __('admin.rentals_done') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topVehicles as $vehicle)
                                <tr>
                                    <td>
                                        <a class="fw-semibold text-decoration-none" href="{{ route('admin.vehicles.show', $vehicle) }}">
                                            {{ $vehicle->displayName() }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ $vehicle->rentals_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted small">{{ __('admin.no_rental_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.over_period') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.driver_activity') }}</h2>
                    </div>
                    <i class="bi bi-person-badge text-primary fs-4"></i>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin.driver') }}</th>
                                <th class="text-end">{{ __('admin.trips') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($driverActivity as $line)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="admin-avatar admin-avatar--sm">{{ $line['driver']->initials() }}</span>
                                            <span>{{ $line['driver']->displayName() }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ $line['trips'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted small">{{ __('admin.no_driver') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <p class="small text-muted mt-3">{{ __('admin.actual_revenue_hint') }}</p>
@endsection
