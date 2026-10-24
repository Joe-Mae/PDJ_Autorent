@extends('layouts.panel')

@section('title', __('admin.dashboard_title'))
@section('breadcrumb', __('admin.dashboard_title'))

@section('content')
    @php
        $isManager = auth()->user()?->hasAnyRole(['gestionnaire', 'super_admin']);
        $chartMax = max(1, collect($bookingSeries)->max('count'));
        $serviceTotal = max(1, array_sum($serviceBreakdown));
        $services = [
            ['label' => __('admin.service_car_only'), 'count' => $serviceBreakdown['car_only'], 'tone' => 'primary'],
            ['label' => __('admin.service_with_driver'), 'count' => $serviceBreakdown['with_driver'], 'tone' => 'success'],
            ['label' => __('admin.service_driver_only'), 'count' => $serviceBreakdown['driver_only'], 'tone' => 'warning'],
        ];

        $operationalCards = [
            [__('admin.total_bookings'), $stats['total_bookings'], 'calendar2-check', 'primary', route('admin.bookings.index')],
            [__('admin.pending_bookings'), $stats['pending'], 'hourglass-split', 'warning', route('admin.bookings.index', ['statut' => 'en_attente'])],
            [__('admin.confirmed_bookings'), $stats['confirmed'], 'calendar-check', 'info', route('admin.bookings.index', ['statut' => 'confirmee'])],
            [__('admin.available_vehicles'), $stats['available'], 'car-front-fill', 'success', $isManager ? route('admin.vehicles.index') : null],
            [__('admin.rented_vehicles'), $stats['en_location'], 'key', 'primary', route('admin.bookings.index', ['statut' => 'en_cours'])],
            [__('admin.available_drivers'), $stats['chauffeurs'], 'person-badge', 'success', $isManager ? route('admin.drivers.index') : null],
        ];

        $alertCards = [
            [__('admin.period_revenue'), app(\App\Services\SettingsService::class)->money($periodRevenue), 'cash-stack', 'success', route('admin.payments.index')],
            [__('admin.outstanding'), app(\App\Services\SettingsService::class)->money($outstanding), 'hourglass-bottom', 'danger', route('admin.payments.index')],
            [__('admin.overdue_rentals'), $stats['overdue'], 'exclamation-triangle', 'danger', route('admin.bookings.index', ['statut' => 'en_cours'])],
            [__('admin.unavailable_vehicles'), $stats['unavailable'], 'tools', 'warning', $isManager ? route('admin.vehicles.index') : null],
        ];
    @endphp

    <section class="admin-dashboard">
        <x-admin.page-header
            :eyebrow="now()->translatedFormat('l d F Y')"
            :title="__('admin.dashboard_title')"
            :subtitle="__('admin.dashboard_subtitle')">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
                <label class="visually-hidden" for="periode">{{ __('admin.period') }}</label>
                <select class="form-select form-select-sm admin-period-select" name="periode" id="periode" onchange="this.form.submit()">
                    @foreach (['semaine' => __('admin.this_week'), 'mois' => __('admin.this_month'), 'annee' => __('admin.this_year')] as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <a class="btn btn-outline-primary" href="{{ route('admin.bookings.index') }}">
                <i class="bi bi-calendar-check me-1"></i>{{ __('admin.bookings') }}
            </a>
            <a class="btn btn-primary" href="{{ route('agent.pickup.index') }}">
                <i class="bi bi-box-arrow-right me-1"></i>{{ __('admin.start_pickup') }}
            </a>
        </x-admin.page-header>

        {{-- â”€â”€ Indicateurs du parc et des rÃ©servations â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="row g-3 mb-3">
            @foreach ($operationalCards as [$label, $value, $icon, $tone, $url])
                <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                    <div class="pdj-card admin-stat-card admin-stat-card--compact h-100">
                        <div class="admin-stat-card__icon text-{{ $tone }}">
                            <i class="bi bi-{{ $icon }}"></i>
                        </div>
                        <div class="admin-stat-card__label">{{ $label }}</div>
                        <div class="admin-stat-card__value">{{ number_format($value, 0, ',', ' ') }}</div>
                        @if ($url)
                            <a class="admin-stat-card__link" href="{{ $url }}" aria-label="{{ $label }}">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- â”€â”€ Argent et alertes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="row g-3 mb-4">
            @foreach ($alertCards as [$label, $value, $icon, $tone, $url])
                <div class="col-6 col-lg-3">
                    <div class="pdj-card admin-stat-card admin-stat-card--compact h-100">
                        <div class="admin-stat-card__icon text-{{ $tone }}">
                            <i class="bi bi-{{ $icon }}"></i>
                        </div>
                        <div class="admin-stat-card__label">{{ $label }}</div>
                        <div class="admin-stat-card__value">{{ $value }}</div>
                        @if ($url)
                            <a class="admin-stat-card__link" href="{{ $url }}" aria-label="{{ $label }}">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="admin-quick-links d-flex flex-wrap align-items-center gap-2 mb-4">
            <span class="small fw-semibold text-muted me-1">{{ __('admin.quick_access') }}</span>
            <a class="btn btn-sm btn-light border" href="{{ route('agent.return.index') }}">
                <i class="bi bi-box-arrow-in-left me-1"></i>{{ __('admin.returns') }}
            </a>
            <a class="btn btn-sm btn-light border" href="{{ route('admin.documents.index') }}">
                <i class="bi bi-file-earmark-check me-1"></i>{{ __('admin.documents') }}
            </a>
            <a class="btn btn-sm btn-light border" href="{{ route('admin.payments.index') }}">
                <i class="bi bi-cash-stack me-1"></i>{{ __('admin.cash') }}
            </a>
            @if ($isManager)
                <a class="btn btn-sm btn-light border" href="{{ route('admin.vehicles.index') }}">
                    <i class="bi bi-car-front me-1"></i>{{ __('admin.vehicles') }}
                </a>
                <a class="btn btn-sm btn-light border" href="{{ route('admin.driver-only-bookings.index') }}">
                    <i class="bi bi-person-lines-fill me-1"></i>{{ __('admin.driver_only_requests') }}
                </a>
                <a class="btn btn-sm btn-light border" href="{{ route('admin.statistics.index') }}">
                    <i class="bi bi-bar-chart-line me-1"></i>{{ __('admin.statistics') }}
                </a>
            @endif
        </div>

        {{-- â”€â”€ Graphique et rÃ©partition â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-8">
                <section class="pdj-card h-100">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.last_12_weeks') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.bookings_evolution') }}</h2>
                        </div>
                        <span class="badge rounded-pill text-bg-light border">
                            {{ number_format(array_sum(array_column($bookingSeries, 'count')), 0, ',', ' ') }} {{ __('admin.bookings') }}
                        </span>
                    </div>

                    <div class="admin-chart" role="img" aria-label="{{ __('admin.bookings_evolution') }}">
                        @foreach ($bookingSeries as $point)
                            <div class="admin-chart__col">
                                <span class="admin-chart__value {{ $point['count'] === 0 ? 'is-empty' : '' }}">{{ $point['count'] }}</span>
                                <div class="admin-chart__track">
                                    <div class="admin-chart__bar" style="height: {{ max(4, round($point['count'] / $chartMax * 100)) }}%;"></div>
                                </div>
                                <span class="admin-chart__label">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
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

                    <div class="admin-cash-row mt-2">
                        <span>{{ __('admin.period_revenue') }}</span>
                        <strong class="text-success">{{ app(\App\Services\SettingsService::class)->money($periodRevenue) }}</strong>
                    </div>
                    <div class="admin-cash-row">
                        <span>{{ __('admin.outstanding') }}</span>
                        <strong class="{{ $outstanding > 0 ? 'text-danger' : '' }}">{{ app(\App\Services\SettingsService::class)->money($outstanding) }}</strong>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xxl-8">
                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.action_required') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.to_validate') }}</h2>
                        </div>
                        <span class="badge rounded-pill text-bg-warning">{{ $pending['validation']->count() }}</span>
                    </div>

                    @forelse ($pending['validation'] as $booking)
                        <article class="admin-list-item">
                            <div class="admin-list-item__main">
                                <a class="fw-bold text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">
                                    {{ $booking->reference }}
                                </a>
                                <span class="text-muted mx-1">Â·</span>
                                <span>{{ $booking->vehicle->displayName() }}</span>
                                <div class="small text-muted mt-1">
                                    {{ $booking->user?->displayName() ?? $booking->customer_name ?? 'â€”' }}
                                    Â· {{ $booking->user?->phone ?? $booking->customer_phone ?? 'â€”' }}
                                    Â· {{ $booking->starts_at->translatedFormat('D d M H:i') }}
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.documents.index') }}">
                                    {{ __('admin.check_documents') }}
                                </a>
                                <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST"
                                      onsubmit="return confirm(&quot;{{ __('admin.confirm_booking_prompt') }}&quot;)">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">{{ __('admin.confirm') }}</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="admin-empty-state">
                            <i class="bi bi-check2-circle"></i>
                            <span>{{ __('admin.nothing_to_validate') }}</span>
                        </div>
                    @endforelse
                </section>

                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.activity') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.latest_bookings') }}</h2>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.bookings.index') }}">
                            {{ __('admin.see_all') }} <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('admin.reference') }}</th>
                                    <th>{{ __('admin.client') }}</th>
                                    <th>{{ __('admin.vehicle') }}</th>
                                    <th>{{ __('admin.period') }}</th>
                                    <th class="text-end">{{ __('admin.total') }}</th>
                                    <th>{{ __('admin.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentBookings as $booking)
                                    <tr>
                                        <td>
                                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">
                                                {{ $booking->reference }}
                                            </a>
                                        </td>
                                        <td>{{ $booking->user?->displayName() ?? $booking->customer_name ?? 'â€”' }}</td>
                                        <td>{{ $booking->vehicle->displayName() }}</td>
                                        <td class="small text-nowrap">{{ $booking->starts_at->translatedFormat('d/m/Y') }} â€” {{ $booking->ends_at->translatedFormat('d/m/Y') }}</td>
                                        <td class="text-end text-nowrap">{{ app(\App\Services\SettingsService::class)->money($booking->price_total) }}</td>
                                        <td>
                                            @if ($booking->isOverdue())
                                                <span class="badge rounded-pill bg-danger">{{ __('admin.overdue') }}</span>
                                            @else
                                                <span class="badge rounded-pill bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-light border" href="{{ route('admin.bookings.show', $booking) }}"
                                               aria-label="{{ __('admin.view_details') }}">
                                                <i class="bi bi-arrow-up-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('admin.no_booking_short') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="pdj-card">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.today') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.departures_today') }}</h2>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('agent.pickup.index') }}">
                            {{ __('admin.all_departures') }}
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('admin.hour') }}</th>
                                    <th>{{ __('admin.reference') }}</th>
                                    <th>{{ __('admin.vehicle') }}</th>
                                    <th>{{ __('admin.client') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($today['departures'] as $booking)
                                    <tr>
                                        <td>{{ sprintf('%02dh00', $booking->pickup_hour) }}</td>
                                        <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                                        <td>{{ $booking->vehicle->displayName() }}</td>
                                        <td>{{ $booking->user?->displayName() ?? $booking->customer_name ?? 'â€”' }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('agent.pickup.documents', $booking) }}">
                                                {{ __('admin.pickup') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('admin.no_departure_today') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="col-12 col-xxl-4">
                @if ($today['overdue']->isNotEmpty())
                    <section class="admin-alert-panel admin-alert-panel--danger mb-4">
                        <div class="admin-section-heading">
                            <div>
                                <span class="admin-eyebrow">{{ __('admin.action_required') }}</span>
                                <h2 class="h5 fw-bold mb-0">{{ __('admin.overdue_rentals') }}</h2>
                            </div>
                            <span class="badge rounded-pill bg-danger">{{ $today['overdue']->count() }}</span>
                        </div>
                        @foreach ($today['overdue'] as $booking)
                            @php($latePhone = $booking->user?->phone ?? $booking->customer_phone)
                            <div class="admin-list-item">
                                <div class="admin-list-item__main">
                                    <a class="fw-bold" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a>
                                    <div class="small">{{ $booking->vehicle->displayName() }}</div>
                                    <div class="small text-muted">{{ $booking->user?->displayName() ?? $booking->customer_name ?? 'â€”' }}</div>
                                </div>
                                @if ($latePhone)
                                    <a class="btn btn-sm btn-danger" href="tel:{{ $latePhone }}" aria-label="{{ __('admin.call_customer') }}">
                                        <i class="bi bi-telephone"></i>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.next_seven_days') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.upcoming_prestations') }}</h2>
                        </div>
                        <i class="bi bi-calendar2-week text-primary fs-4"></i>
                    </div>
                    @forelse ($upcoming as $prestation)
                        <a class="admin-prestation text-decoration-none" href="{{ $prestation['url'] }}">
                            <span class="admin-prestation__date">
                                <strong>{{ $prestation['at']->translatedFormat('d') }}</strong>
                                <small>{{ $prestation['at']->translatedFormat('M') }}</small>
                            </span>
                            <span class="admin-prestation__main">
                                <span class="fw-semibold">{{ $prestation['client'] }}</span>
                                <small>{{ $prestation['resource'] }} Â· {{ $prestation['reference'] }}</small>
                            </span>
                            <span class="admin-prestation__type">{{ __('admin.service_'.$prestation['type']) }}</span>
                        </a>
                    @empty
                        <div class="admin-empty-state">
                            <i class="bi bi-calendar2-check"></i>
                            <span>{{ __('admin.no_upcoming_prestation') }}</span>
                        </div>
                    @endforelse
                </section>

                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.next_seven_days') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.upcoming_returns') }}</h2>
                        </div>
                        <i class="bi bi-box-arrow-in-left text-primary fs-4"></i>
                    </div>
                    @forelse ($upcomingReturns as $booking)
                        @php($returnPhone = $booking->user?->phone ?? $booking->customer_phone)
                        <article class="admin-return-item {{ $booking->isOverdue() ? 'is-overdue' : '' }}">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <a class="fw-bold text-decoration-none" href="{{ route('admin.bookings.show', $booking) }}">
                                    {{ $booking->reference }}
                                </a>
                                @if ($booking->isOverdue())
                                    <span class="badge bg-danger">{{ __('admin.overdue') }}</span>
                                @endif
                            </div>
                            <div class="small fw-semibold mt-1">{{ $booking->vehicle->displayName() }}</div>
                            <div class="small text-muted">{{ $booking->ends_at->translatedFormat('D d M Â· H:i') }}</div>
                            <div class="small mt-1">
                                {{ $booking->user?->displayName() ?? $booking->customer_name ?? 'â€”' }}
                                @if ($returnPhone)
                                    Â· <a href="tel:{{ $returnPhone }}">{{ $returnPhone }}</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="admin-empty-state">
                            <i class="bi bi-calendar2-check"></i>
                            <span>{{ __('admin.no_upcoming_returns') }}</span>
                        </div>
                    @endforelse
                    <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('agent.return.index') }}">
                        {{ __('admin.open_returns') }}
                    </a>
                </section>

                <section class="pdj-card mb-4">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.actual_cash_flow') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.cash_today') }}</h2>
                        </div>
                        <i class="bi bi-cash-coin text-success fs-4"></i>
                    </div>
                    <div class="admin-cash-row"><span>{{ __('admin.rentals') }}</span><strong>{{ app(\App\Services\SettingsService::class)->money($cash['today_in']) }}</strong></div>
                    <div class="admin-cash-row"><span>{{ __('admin.refunds') }}</span><strong>âˆ’ {{ app(\App\Services\SettingsService::class)->money($cash['today_out']) }}</strong></div>
                    <div class="admin-cash-row admin-cash-row--total"><span>{{ __('admin.deposits_held') }}</span><strong>{{ app(\App\Services\SettingsService::class)->money($cash['deposits_held']) }}</strong></div>
                    <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('agent.cash.index') }}">
                        {{ __('admin.open_cash') }}
                    </a>
                </section>

                <section class="pdj-card">
                    <div class="admin-section-heading">
                        <div>
                            <span class="admin-eyebrow">{{ __('admin.action_required') }}</span>
                            <h2 class="h5 fw-bold mb-0">{{ __('admin.documents_queue') }}</h2>
                        </div>
                        <a class="small" href="{{ route('admin.documents.index') }}">{{ __('admin.see_all') }}</a>
                    </div>
                    @forelse ($pending['documents'] as $document)
                        <a class="admin-document-item text-decoration-none" href="{{ route('admin.documents.show', $document) }}">
                            <span class="admin-document-item__icon"><i class="bi bi-file-earmark-text"></i></span>
                            <span><strong>{{ $document->type->label() }}</strong><small>{{ $document->user?->displayName() ?? 'â€”' }} @if ($document->booking) Â· {{ $document->booking->reference }} @endif</small></span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    @empty
                        <div class="admin-empty-state">
                            <i class="bi bi-check2-circle"></i>
                            <span>{{ __('admin.no_document') }}</span>
                        </div>
                    @endforelse
                </section>
            </aside>
        </div>
    </section>
@endsection
