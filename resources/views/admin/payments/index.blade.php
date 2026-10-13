@extends('layouts.panel')

@section('title', 'Caisse')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.cash') }}</h1>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payments.report') }}">{{ __('admin.daily_report') }}</a>
    </div>

    <div class="pdj-card mb-4">
        <h2 class="h6 fw-bold mb-3">{{ __('admin.new_payment') }}</h2>
        <form method="POST" action="{{ route('admin.payments.store') }}">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="booking_id">{{ __('admin.reference') }}</label>
                    <select class="form-select" id="booking_id" name="booking_id" required>
                        <option value="">{{ __('admin.choose_booking') }}</option>
                        @foreach (\App\Models\Booking::query()->with('vehicle')->orderByDesc('starts_at')->limit(60)->get() as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->reference }} â€” {{ $item->vehicle->displayName() }} ({{ $item->status->label() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="kind">{{ __('admin.type') }}</label>
                    <select class="form-select" id="kind" name="kind">
                        @foreach ($kinds as $kind)
                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="method">{{ __('admin.method') }}</label>
                    <select class="form-select" id="method" name="method">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="amount">{{ __('admin.amount') }}</label>
                    <input type="number" class="form-control" id="amount" name="amount" min="1" step="500" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="reference">{{ __('admin.transaction_ref') }}</label>
                    <input type="text" class="form-control" id="reference" name="reference">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100">{{ __('admin.add') }}</button>
                </div>
            </div>
        </form>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="date" class="form-control" name="jour" value="{{ request('jour', now()->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="kind">
                <option value="">{{ __('admin.all') }}</option>
                @foreach ($kinds as $kind)
                    <option value="{{ $kind->value }}" @selected(request('kind') === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">{{ __('admin.filter') }}</button>
        </div>
    </form>

    <div class="pdj-card p-0 overflow-hidden">
        <table class="table admin-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('admin.hour') }}</th>
                    <th>{{ __('admin.type') }}</th>
                    <th>{{ __('admin.booking') }}</th>
                    <th>{{ __('admin.method') }}</th>
                    <th>{{ __('admin.note') }}</th>
                    <th class="text-end">{{ __('admin.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td class="small">{{ $payment->recorded_at->format('H:i') }}</td>
                        <td class="small fw-semibold">{{ $payment->kind->label() }}</td>
                        <td class="small">
                            <a href="{{ route('admin.bookings.show', $payment->booking) }}">{{ $payment->booking->reference }}</a>
                        </td>
                        <td class="small">{{ $payment->method->label() }}</td>
                        <td class="small text-muted">{{ $payment->note ?? $payment->reference }}</td>
                        <td class="text-end small fw-semibold">{{ number_format($payment->amount, 0, ' ', ' ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('admin.no_payment') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
@endsection