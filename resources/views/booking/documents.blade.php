@extends('layouts.app')

@section('title', __('booking.step.documents'))

@php
    $isGuest = $isGuest ?? false;
    $guestToken = $guestToken ?? null;
    $reusable = $reusable ?? [];

    $storeUrl = $isGuest
        ? route('booking.guest.documents.store', ['locale' => $locale, 'booking' => $booking, 'token' => $guestToken])
        : route('booking.documents.store', ['locale' => $locale, 'booking' => $booking]);

    $destroyUrl = fn ($document) => $isGuest
        ? route('booking.guest.documents.destroy', ['locale' => $locale, 'booking' => $booking, 'token' => $guestToken, 'document' => $document])
        : route('booking.documents.destroy', ['locale' => $locale, 'booking' => $booking, 'document' => $document]);

    $identityCard = \App\Enums\DocumentType::IdentityCard;
    $missing = collect([$identityCard])
        ->reject(fn ($type) => $booking->documents->contains('type', $type));
@endphp

@section('content')

<div class="container py-4" style="max-width: 860px;">

    @unless ($isGuest)
        <x-steps :current="4" />
    @endunless

    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="h4 fw-bold mb-1">{{ __('booking.documents_title') }}</h1>
            <p class="text-muted mb-4">{{ __('booking.documents_intro') }}</p>

            @if ($booking->with_driver)
                <div class="alert alert-success d-flex gap-2">
                    <i class="bi bi-person-check-fill"></i>
                    <span class="small">{{ __('driver.no_license_needed') }}</span>
                </div>
            @endif

            @foreach ($required as $type)
                @php $existing = $booking->documents->firstWhere('type', $type) @endphp

                <div class="pdj-card mb-3">
                    <div class="section-title">{{ $type->label() }}</div>

                    @if ($existing)
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $existing->temporaryUrl() }}" class="upload-preview" alt="{{ $type->label() }}">
                            <div class="flex-grow-1">
                                <span class="badge bg-{{ $existing->status->color() }}">{{ $existing->status->label() }}</span>
                                <div class="small text-muted mt-1">{{ $existing->humanSize() }}</div>
                            </div>
                            <form action="{{ $destroyUrl($existing) }}"
                                  method="POST" onsubmit="return confirm('{{ __('document.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('document.replace') }}</button>
                            </form>
                        </div>
                    @elseif (isset($reusable[$type->value]))
                        {{-- Document déjà vérifié lors d'une location précédente : on le réutilise. --}}
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-success"></i>
                            <span class="small">
                                {{ __('document.upload_reuse', ['date' => $reusable[$type->value]->reviewed_at?->translatedFormat('d/m/Y') ?? '—']) }}
                            </span>
                        </div>
                        <form action="{{ $storeUrl }}"
                              method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type->value }}">
                            <input type="hidden" name="copy_from" value="{{ $reusable[$type->value]->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                {{ __('document.use_this_one') }}
                            </button>
                        </form>
                    @else
                        <form action="{{ $storeUrl }}"
                              method="POST" enctype="multipart/form-data" data-upload-zone>
                            @csrf
                            <input type="hidden" name="type" value="{{ $type->value }}">

                            <div class="upload-zone">
                                <i class="bi bi-camera fs-2 text-muted"></i>
                                <p class="small mb-2" data-upload-name>{{ __('document.upload_cta') }}</p>
                                <label class="btn btn-outline-primary btn-sm">
                                    {{ __('document.upload_camera') }}
                                    <input type="file" name="file"
                                          accept="{{ $type === \App\Enums\DocumentType::IdentityCard ? 'image/jpeg,image/png' : '.jpg,.jpeg,.png,.pdf' }}"
                                          @if ($type === \App\Enums\DocumentType::IdentityCard) capture="environment" @endif
                                          data-upload class="d-none" required>
                                </label>
                                <p class="form-text mb-0 mt-2">{{ __('document.upload_hint') }}</p>
                            </div>

                            <img data-upload-preview src="" alt="" class="upload-preview mt-3 d-none">

                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                {{ __('document.upload') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach

            {{-- Le selfie aide à confirmer que le papier est bien le vôtre.
                 Volontairement optionnel : on ne fait pas fuir pour ça. --}}
            @php $selfie = \App\Enums\DocumentType::Selfie @endphp
            @if (! $booking->documents->contains('type', $selfie) && app(\App\Services\SettingsService::class)->bool('document.selfie_enabled'))
                <details class="pdj-card">
                    <summary class="small fw-semibold">{{ __('document.selfie_title') }}</summary>
                    <p class="small text-muted mt-2 mb-3">{{ __('document.selfie_hint') }}</p>
                    <form action="{{ $storeUrl }}"
                          method="POST" enctype="multipart/form-data" data-upload-zone>
                        @csrf
                        <input type="hidden" name="type" value="selfie">
                        <div class="upload-zone">
                            <i class="bi bi-person-bounding-box fs-2 text-muted"></i>
                            <label class="btn btn-outline-primary btn-sm mt-2">
                                {{ __('document.take_selfie') }}
                                <input type="file" name="file" accept="image/jpeg,image/png" data-upload class="d-none">
                            </label>
                        </div>
                        <img data-upload-preview src="" alt="" class="upload-preview mt-3 d-none">
                        <button type="submit" class="btn btn-outline-primary w-100 mt-3">{{ __('document.upload') }}</button>
                    </form>
                </details>
            @endif

            @if ($isGuest)
                <div class="pdj-card mt-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <div class="small text-muted">
                        @if ($missing->isEmpty())
                            <i class="bi bi-check-circle-fill text-success me-1"></i>{{ __('booking.guest.documents_complete') }}
                        @else
                            <i class="bi bi-info-circle me-1"></i>{{ __('booking.guest.documents_required') }}
                            <ul class="mb-0 mt-2 ps-4">
                                @foreach ($missing as $type)
                                    <li>{{ $type->label() }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if ($missing->isEmpty())
                        <a class="btn btn-primary"
                           href="{{ route('booking.guest.confirmation', ['locale' => $locale, 'booking' => $booking, 'token' => $guestToken]) }}">
                            {{ __('booking.guest.documents_finish') }}
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-primary" disabled>
                            {{ __('booking.guest.documents_finish') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="section-title">{{ __('booking.cost_summary') }}</div>
            <x-price-ticket :quote="$quote" />

            <div class="pdj-card mt-3 small">
                <div class="section-title">{{ __('document.privacy_title') }}</div>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-lock me-1"></i>{{ __('document.privacy_storage') }}</li>
                    <li class="mb-2"><i class="bi bi-eye-slash me-1"></i>{{ __('document.privacy_access') }}</li>
                    <li class="mb-0"><i class="bi bi-trash me-1"></i>{{ __('document.privacy_purge') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
