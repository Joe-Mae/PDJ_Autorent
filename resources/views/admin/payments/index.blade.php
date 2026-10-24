@extends('layouts.panel')

@section('title', __('admin.cash'))
@section('breadcrumb', __('admin.cash'))

@section('content')
    <x-admin.page-header
        :eyebrow="$day->translatedFormat('l d F Y')"
        :title="__('admin.cash')"
        :subtitle="__('admin.cash_subtitle')">
        <a class="btn btn-outline-primary" href="{{ route('admin.payments.report') }}">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>{{ __('admin.daily_report') }}
        </a>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        @foreach ([
            [__('admin.rentals'), $dayTotals['rental'], 'cash-stack', 'success'],
            [__('admin.cash_deposits'), $dayTotals['deposits'], 'shield-check', 'primary'],
            [__('admin.refunds'), $dayTotals['refunds'], 'arrow-return-left', 'warning'],
            [__('admin.outstanding'), $outstanding, 'hourglass-bottom', 'danger'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-6 col-xl-3">
                <div class="pdj-card admin-stat-card admin-stat-card--compact h-100">
                    <div class="admin-stat-card__icon text-{{ $tone }}">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                    <div class="admin-stat-card__label">{{ $label }}</div>
                    <div class="admin-stat-card__value">{{ app(\App\Services\SettingsService::class)->money($value) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.actual_cash_flow') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.new_payment') }}</h2>
                    </div>
                    <i class="bi bi-plus-circle text-primary fs-4"></i>
                </div>

                <form method="POST" action="{{ route('admin.payments.store') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="booking_id">{{ __('admin.reference') }}</label>
                            <select class="form-select" id="booking_id" name="booking_id" required>
                                <option value="">{{ __('admin.choose_booking') }}</option>
                                @foreach (\App\Models\Booking::query()->with('vehicle')->orderByDesc('starts_at')->limit(60)->get() as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->reference }} — {{ $item->vehicle->displayName() }} ({{ $item->status->label() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="kind">{{ __('admin.type') }}</label>
                            <select class="form-select" id="kind" name="kind">
                                @foreach ($kinds as $kind)
                                    <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="method">{{ __('admin.method') }}</label>
                            <select class="form-select" id="method" name="method">
                                @foreach ($methods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="amount">{{ __('admin.amount') }}</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="1" step="500" required>
                        </div>
                        <div class="col-6 col-md-5">
                            <label class="form-label" for="reference">{{ __('admin.transaction_ref') }}</label>
                            <input type="text" class="form-control" id="reference" name="reference">
                        </div>
                        <div class="col-12 col-md-3">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-check2 me-1"></i>{{ __('admin.add') }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="pdj-card h-100">
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-eyebrow">{{ __('admin.action_required') }}</span>
                        <h2 class="h5 fw-bold mb-0">{{ __('admin.outstanding') }}</h2>
                    </div>
                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                        {{ $unpaidBookings->count() }}
                    </span>
                </div>

                @forelse ($unpaidBookings as $line)
                    <a class="admin-prestation text-decoration-none"
                       href="{{ route('admin.bookings.show', $line['booking']) }}">
                        <span class="admin-prestation__main">
                            <span class="fw-semibold">{{ $line['booking']->reference }}</span>
                            <small>{{ __('admin.remaining_amount', ['amount' => app(\App\Services\SettingsService::class)->money($line['remaining'])]) }}</small>
                        </span>
                        <span class="admin-prestation__type">{{ __('admin.to_collect') }}</span>
                    </a>
                @empty
                    <div class="admin-empty-state">
                        <i class="bi bi-check2-circle"></i>
                        <span>{{ __('admin.nothing_outstanding') }}</span>
                    </div>
                @endforelse

                <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('admin.bookings.index') }}">
                    {{ __('admin.see_all') }} <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </section>
        </div>
    </div>

    <form method="GET" class="pdj-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="jour">{{ __('admin.date') }}</label>
                <input type="date" class="form-control" id="jour" name="jour" value="{{ $day->toDateString() }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="kind-filter">{{ __('admin.type') }}</label>
                <select class="form-select" id="kind-filter" name="kind">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($kinds as $kind)
                        <option value="{{ $kind->value }}" @selected(request('kind') === $kind->value)>{{ $kind->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-5 d-flex justify-content-end gap-2">
                <a class="btn btn-light border" href="{{ route('admin.payments.index') }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('admin.reset_filters') }}
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('admin.filter') }}
                </button>
            </div>
        </div>
    </form>

    <div class="pdj-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin.hour') }}</th>
                        <th>{{ __('admin.type') }}</th>
                        <th>{{ __('admin.booking') }}</th>
                        <th>{{ __('admin.method') }}</th>
                        <th>{{ __('admin.recorded_by') }}</th>
                        <th>{{ __('admin.note') }}</th>
                        <th class="text-end">{{ __('admin.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="small text-nowrap">{{ $payment->recorded_at->format('H:i') }}</td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $payment->kind->isCashOut() ? 'danger' : 'success' }}-subtle text-{{ $payment->kind->isCashOut() ? 'danger' : 'success' }}-emphasis border">
                                    {{ $payment->kind->label() }}
                                </span>
                            </td>
                            <td class="small">
                                <a class="text-decoration-none" href="{{ route('admin.bookings.show', $payment->booking) }}">
                                    {{ $payment->booking->reference }}
                                </a>
                                <div class="text-muted">{{ $payment->booking->vehicle->displayName() }}</div>
                            </td>
                            <td class="small">{{ $payment->method->label() }}</td>
                            <td class="small">{{ $payment->recorder?->displayName() ?? '—' }}</td>
                            <td class="small text-muted">{{ $payment->note ?? $payment->reference ?? '—' }}</td>
                            <td class="text-end text-nowrap fw-semibold {{ $payment->kind->isCashOut() ? 'text-danger' : '' }}">
                                {{ $payment->kind->isCashOut() ? '−' : '' }}{{ app(\App\Services\SettingsService::class)->money($payment->amount) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="admin-empty-state">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>{{ __('admin.no_payment') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
@endsection
