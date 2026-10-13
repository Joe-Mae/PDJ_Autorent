@extends('layouts.app')

@section('title', $pageTitle ?? $title ?? '')

@section('content')
    <div @class([
        'container-fluid',
        'page-content',
        'page-content--wide' => $wide ?? false,
        'py-5',
    ])>

        @isset($pageTitle)
            <h1 class="h3 fw-bold mb-2">{{ $pageTitle }}</h1>

            @isset($pageIntro)
                <p class="lead text-muted mb-4">{{ $pageIntro }}</p>
            @endisset
        @endisset

        @yield('pageContent')
    </div>
@endsection
