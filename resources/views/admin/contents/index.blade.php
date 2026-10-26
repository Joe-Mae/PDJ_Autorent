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

    <h2 class="h6 text-uppercase text-muted mt-4 mb-2">Textes de l’accueil</h2>

    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <a class="d-block text-decoration-none text-reset pdj-card h-100" href="{{ route('admin.contents.driver.edit') }}">
                <div class="display-6 fw-bold" style="color: var(--pdj-green);"><i class="bi bi-car-front"></i></div>
                <div class="fw-semibold">Bloc chauffeur</div>
                <div class="small text-muted">Badge, arguments, mariages, bouton <i class="bi bi-arrow-right"></i></div>
            </a>
        </div>
    </div>
@endsection
