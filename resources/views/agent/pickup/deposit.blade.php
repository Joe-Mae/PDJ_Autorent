@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Caution')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 4/5</div>
                <h1 class="h4 fw-bold">{{ __('agent.collect_deposit') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->user?->displayName() }}</p>
            </div>

            <div class="alert alert-info d-flex gap-2">
                <i class="bi bi-info-circle-fill"></i>
                <span class="small">{{ __('agent.deposit_receipt_hint') }}</span>
            </div>

            <form method="POST" action="{{ route('agent.pickup.deposit.store', $booking) }}">
                @csrf
                <div class="pdj-card mb-3">
                    <label class="form-label" for="amount">{{ __('page.deposit_title') }} â€” {{ $booking->vehicle->category->name('fr') }}</label>
                    <input type="number" class="form-control form-control-lg" id="amount" name="amount"
                           value="{{ $booking->deposit_amount }}" min="0" step="10000" required>
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
                    <div class="form-text">{{ __('agent.deposit_methods_hint') }}</div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg">{{ __('agent.continue') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('agent.pickup.index') }}">{{ __('admin.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection