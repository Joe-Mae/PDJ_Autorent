@extends('layouts.panel')

@section('title', 'Rapport de caisse')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.daily_report') }} â€” {{ $date->translatedFormat('d F Y') }}</h1>
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
        <div class="col-lg-6">
            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.by_method') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        @forelse ($byMethod as $method => $total)
                            <tr>
                                <td>{{ \App\Enums\PaymentMethod::from($method)->label() }}</td>
                                <td class="text-end">{{ number_format($total, 0, ' ', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted small">{{ __('admin.no_payment') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-2">{{ __('admin.deposits_held') }}</h2>
                <p class="small text-muted">{{ __('admin.deductions_made') }} : {{ number_format($deducted, 0, ' ', ' ') }} F CFA</p>
                <table class="table admin-table mb-0">
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="small">{{ $payment->recorded_at->format('H:i') }} w {{ $payment->kind->label() }}</td>
                                <td class="small text-muted">{{ $payment->booking->reference }}</td>
                                <td class="text-end small">{{ number_format($payment->amount, 0, ' ', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection