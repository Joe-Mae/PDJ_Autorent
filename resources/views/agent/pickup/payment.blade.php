@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Encaissement de la location')

@section('content')
    @php
        $money = fn (int $amount) => app(\App\Services\SettingsService::class)->money($amount);
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 3/6</div>
                <h1 class="h4 fw-bold">{{ __('agent.collect_rental') }}</h1>
                <p class="text-muted">{{ $booking->reference }} — {{ $booking->user?->displayName() ?? $booking->customer_name ?? '—' }}</p>
            </div>

            <div class="price-ticket mb-3">
                @foreach ($quote->receiptLines() as $line)
                    <div class="price-ticket__row">
                        <span>{{ $line['label'] }}</span>
                        <span class="fw-semibold">{{ $money($line['amount']) }}</span>
                    </div>
                @endforeach
                <div class="price-ticket__sep"></div>
                <div class="price-ticket__total">
                    <span>{{ __('general.pay_at_agency') }}</span>
                    <span>{{ $money($booking->price_total) }}</span>
                </div>
            </div>

            @unless ($booking->requiresDeposit())
                <div class="alert alert-info small">{{ __('agent.no_deposit_required') }}</div>
            @endunless

            <form method="POST" action="{{ route('agent.pickup.payment.store', $booking) }}">
                @csrf
                <div class="pdj-card mb-3">
                    <label class="form-label" for="amount">{{ __('agent.amount_received') }}</label>
                    <input type="number" class="form-control form-control-lg" id="amount" name="amount"
                           value="{{ $booking->price_total }}" min="1" step="500" required>
                    <div class="form-text">{{ __('agent.no_online_payment') }}</div>
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label">{{ __('agent.method') }}</label>
                    @foreach ($methods as $method)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="method" id="m-{{ $method->value }}"
                                   value="{{ $method->value }}" @checked($method->value === 'especes')>
                            <label class="form-check-label" for="m-{{ $method->value }}">{{ $method->label() }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="pdj-card mb-3">
                    <label class="form-label" for="reference">{{ __('agent.transaction_ref') }}</label>
                    <input type="text" class="form-control" id="reference" name="reference">
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection