@extends('layouts.panel', ['section' => 'agent', 'singleAction' => true])

@section('title', 'Restitution de la caution')

@section('content')
    @php $paid = $booking->depositPaid() @endphp

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mb-4">
                <div class="small text-muted">{{ __('agent.step') }} 2/2</div>
                <h1 class="h4 fw-bold">{{ __('agent.refund_deposit') }}</h1>
                <p class="text-muted">{{ $booking->reference }} w {{ $booking->user?->displayName() }}</p>
            </div>

            @if ($paid === 0)
                <div class="alert alert-warning">{{ __('agent.no_deposit') }}</div>
            @else
                <div class="price-ticket mb-3">
                    <div class="price-ticket__row">
                        <span>{{ __('agent.deposit_received') }}</span>
                        <span class="fw-semibold">{{ number_format($paid, 0, ' ', ' ') }}</span>
                    </div>
                    <div class="price-ticket__sep"></div>
                    <div class="price-ticket__total">
                        <span>{{ __('agent.to_return') }}</span>
                        <span id="aRendre">{{ number_format($paid, 0, ' ', ' ') }}</span>                    </div>
                </div>

                <form method="POST" action="{{ route('agent.return.deposit.store', $booking) }}">
                    @csrf
                    <div class="pdj-card mb-3">
                        <label class="form-label" for="deducted_amount">{{ __('agent.deduction') }}</label>
                        <input type="number" class="form-control form-control-lg" id="deducted_amount" name="deducted_amount"
                               value="0" min="0" max="{{ $paid }}" step="500" required>
                        <div class="form-text">{{ __('agent.deduction_hint') }}</div>
                    </div>

                    <div class="pdj-card mb-3">
                        <label class="form-label" for="note">{{ __('agent.deduction_reason') }}</label>
                        <textarea class="form-control" id="note" name="note" rows="2"
                                  placeholder="{{ __('agent.deduction_placeholder') }}"></textarea>
                        <div class="form-text">{{ __('agent.deduction_explain') }}</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg">{{ __('agent.refund') }}</button>
                        <a class="btn btn-outline-secondary" href="{{ route('agent.return.index') }}">{{ __('admin.back') }}</a>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @push('scripts')
        @if ($paid > 0)
            <script>
                const input = document.getElementById('deducted_amount');
                const out = document.getElementById('aRendre');
                const total = {{ (int) $paid }};
                const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' F CFA';
                const refresh = () => { out.textContent = fmt(Math.max(0, total - (Number(input.value) || 0))); };
                input.addEventListener('input', refresh);
                refresh();
            </script>
        @endif
    @endpush
@endsection