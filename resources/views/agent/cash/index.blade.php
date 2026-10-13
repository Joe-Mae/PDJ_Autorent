@extends('layouts.panel', ['section' => 'agent'])

@section('title', 'Caisse du jour')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('agent.cash_of') }} {{ $date->translatedFormat('d F Y') }}</h1>
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="jour" value="{{ $date->toDateString() }}">
            <button class="btn btn-outline-primary">{{ __('admin.filter') }}</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            [__('admin.rentals'), $rental, 'text-success'],
            [__('page.deposit_title'), $deposits, 'text-secondary'],
            [__('admin.extras'), $extras, 'text-warning'],
            [__('admin.refunds'), -$refunds, 'text-danger'],
        ] as [$label, $value, $color])
            <div class="col-6 col-lg-3">
                <div class="pdj-card">
                    <div class="small text-muted">{{ $label }}</div>
                    <div class="h5 fw-bold mb-0 {{ $color }}">{{ number_format($value, 0, ' ', ' ') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pdj-card p-0 overflow-hidden">
                <table class="table admin-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admin.hour') }}</th>
                            <th>{{ __('admin.type') }}</th>
                            <th>{{ __('admin.booking') }}</th>
                            <th>{{ __('admin.method') }}</th>
                            <th class="text-end">{{ __('admin.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="small">{{ $payment->recorded_at->format('H:i') }}</td>
                                <td class="small">{{ $payment->kind->label() }}</td>
                                <td class="small"><a href="{{ route('admin.bookings.show', $payment->booking) }}">{{ $payment->booking->reference }}</a></td>
                                <td class="small">{{ $payment->method->label() }}</td>
                                <td class="text-end small">{{ number_format($payment->amount, 0, ' ', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('agent.no_operation') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.by_method') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        @forelse ($byMethod as $method => $total)
                            <tr>
                                <td>{{ \App\Enums\PaymentMethod::from($method)->label() }}</td>
                                <td class="text-end">{{ number_format($total, 0, ' ', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted small">{{ __('agent.no_operation') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-2">{{ __('agent.close_day') }}</h2>
                <p class="small text-muted">{{ __('agent.close_day_hint') }}</p>
                <form method="POST" action="{{ route('agent.cash.close') }}">
                    @csrf
                    <input type="hidden" name="jour" value="{{ $date->toDateString() }}">
                    <label class="form-label" for="counted">{{ __('agent.counted') }}</label>
                    <input type="number" class="form-control mb-2" id="counted" name="counted" min="0" step="500" required>
                    <input type="text" class="form-control mb-2" name="note" placeholder="{{ __('agent.note') }}">
                    <button class="btn btn-primary w-100">{{ __('agent.close') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection