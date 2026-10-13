@extends('layouts.panel')

@section('title', 'Contenus du site')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('admin.contents') }}</h1>
        <p class="text-muted small">{{ __('admin.contents_hint') }}</p>
    </div>

    <div class="row g-3">
        @foreach ($types as $type)
            <div class="col-md-6 col-lg-3">
                <a class="d-block text-decoration-none text-reset pdj-card h-100" href="{{ route('admin.contents.edit', $type['key']) }}">
                    <div class="display-6 fw-bold" style="color: var(--pdj-green);">{{ $type['count'] }}</div>
                    <div class="fw-semibold">{{ $type['label'] }}</div>
                    <div class="small text-muted">{{ __('admin.manage') }} <i class="bi bi-arrow-right"></i></div>
                </a>
            </div>
        @endforeach
    </div>
@endsection