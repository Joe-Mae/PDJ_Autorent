{{-- Page affichée quand un envoi dépasse post_max_size.

     PHP jette alors la requête entière avant que la session ne démarre : on ne
     peut pas passer par un message flash, il serait perdu. La réponse est donc
     rendue directement, avec un lien de retour vers le formulaire. --}}
@extends('layouts.panel', ['singleAction' => true])

@section('title', __('admin.photo_too_heavy', ['max' => 2]))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="pdj-card p-4 p-lg-5">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="feature-icon flex-shrink-0 mb-0">
                        <i class="bi bi-file-earmark-image"></i>
                    </div>
                    <div>
                        <h1 class="h5 fw-bold mb-1">{{ __('admin.photo_too_heavy', ['max' => 2]) }}</h1>
                        <p class="mb-0 text-muted">{{ __('admin.upload_too_large') }}</p>
                    </div>
                </div>

                <div class="alert alert-warning mb-4">
                    <ul class="mb-0 ps-3 small">
                        <li>{{ __('admin.photos_max_hint') }}</li>
                        <li>{{ __('admin.photos_hint') }}</li>
                    </ul>
                </div>

                <p class="small text-muted mb-4">
                    {{ __('admin.upload_nothing_saved') }}
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ $retour ?? url('/admin/vehicules') }}">
                        <i class="bi bi-arrow-left me-1"></i>{{ __('admin.back') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
@endsection
