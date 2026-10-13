@extends('pages.layout')

@section('title', __('page.pay_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.pay_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.pay_intro') }}</p>

    <div class="pdj-card mb-4" style="border-left: 4px solid var(--pdj-green);">
        <div class="section-title">{{ __('page.pay_online') }}</div>
        <p class="small mb-0">{{ __('page.pay_online_text') }}</p>
    </div>

    <div class="section-title">{{ __('page.pay_methods') }}</div>
    <div class="row g-3 mb-4">
        @foreach ([
            ['especes', 'cash', 'cash-stack'],
            ['mobile_money', 'mobile', 'phone'],
            ['visa', 'visa', 'credit-card-2-front'],
        ] as [$key, $textKey, $icon])
            <div class="col-md-4">
                <div class="pdj-card h-100">
                    <div class="feature-icon"><i class="bi bi-{{ $icon }}"></i></div>
                    <h3 class="h6 fw-bold">{{ __('page.pay_'.$textKey) }}</h3>
                    <p class="small text-muted mb-0">{{ __('page.pay_'.$textKey.'_text') }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="section-title">{{ __('page.pay_what') }}</div>
    <ol class="mb-4">
        <li class="mb-2">{{ __('page.pay_step1') }}</li>
        <li>{{ __('page.pay_step2') }}</li>
    </ol>

    <div class="pdj-card">
        <div class="section-title">{{ __('page.pay_receipts') }}</div>
        <p class="small mb-0">{{ __('page.pay_receipts_text') }}</p>
    </div>
@endsection
