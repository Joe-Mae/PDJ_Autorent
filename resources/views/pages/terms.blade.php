@extends('pages.layout')

@section('title', __('page.terms_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('page.terms_title') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.terms_intro') }}</p>

    @foreach ([
        ['age', ['age' => $minAge, 'years' => $licenseYears]],
        ['pickup', []],
        ['use', ['speed' => $speedLimit]],
        ['fuel', []],
        ['mileage', []],
        ['damage', []],
        ['cancellation', []],
        ['driver', []],
    ] as [$key, $params])
        <div class="pdj-card mb-3">
            <h2 class="h6 fw-bold">{{ __('page.terms_'.$key) }}</h2>
            <p class="small text-muted mb-0">{{ __('page.terms_'.$key.'_text', $params) }}</p>
        </div>
    @endforeach
@endsection
