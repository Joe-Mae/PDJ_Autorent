@extends('pages.layout')

@section('title', __('page.privacy_title'))

@section('pageContent')
    @php
        $settings = app(\App\Services\SettingsService::class);
    @endphp
    <h1 class="h3 fw-bold mb-2">{{ __('page.privacy_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.privacy_intro') }}</p>

    @foreach ([
        'what' => [],
        'why' => [],
        'docs' => [
            'months_identity' => $settings->int('document.identity_retention_months'),
            'months_common' => $settings->int('document.retention_months'),
        ],
        'rights' => [],
    ] as $key => $params)
        <div class="pdj-card mb-3">
            <h2 class="h6 fw-bold">{{ __('page.privacy_'.$key) }}</h2>
            <p class="small text-muted mb-0">{{ __('page.privacy_'.$key.'_text', $params) }}</p>
        </div>
    @endforeach
@endsection
