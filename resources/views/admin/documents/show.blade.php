@extends('layouts.panel')

@section('title', 'VÃ©rifier : '.$document->type->label())

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">
            {{ $document->type->label() }}
            <span class="badge bg-{{ $document->status->color() }} ms-2">{{ $document->status->label() }}</span>
        </h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.documents.index') }}">{{ __('admin.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.document') }}</h2>
                <a href="{{ $document->temporaryUrl() }}" target="_blank" rel="noopener">
                    <img src="{{ $document->temporaryUrl() }}" alt="{{ $document->type->label() }}"
                         class="img-fluid rounded" style="max-height: 70vh;">
                </a>
                <p class="small text-muted mt-2 mb-0">
                    {{ $document->original_filename }} w {{ $document->humanSize() }} w
                    {{ $document->created_at->translatedFormat('d/m/Y H:i') }}
                </p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="pdj-card mb-4">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.check_against') }}</h2>
                <table class="table admin-table mb-0">
                    <tbody>
                        <tr><td>{{ __('booking.field_first_name') }}</td><td class="text-end fw-semibold">{{ $document->user?->first_name }}</td></tr>
                        <tr><td>{{ __('booking.field_last_name') }}</td><td class="text-end fw-semibold">{{ $document->user?->last_name }}</td></tr>
                        <tr><td>{{ __('booking.field_birth_date') }}</td><td class="text-end">{{ $document->user?->birth_date?->format('d/m/Y') }}</td></tr>
                        <tr><td>{{ __('booking.field_phone') }}</td><td class="text-end">{{ $document->user?->phone }}</td></tr>
                        @if ($document->booking)
                            <tr><td>{{ __('admin.reservation') }}</td><td class="text-end">
                                <a href="{{ route('admin.bookings.show', $document->booking) }}">{{ $document->booking->reference }}</a>
                            </td></tr>
                            <tr><td>{{ __('admin.vehicle') }}</td><td class="text-end">{{ $document->booking->vehicle->displayName() }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-3">{{ __('admin.verdict') }}</h2>
                <form action="{{ route('admin.documents.review', $document) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="expires_at">{{ __('admin.document_expiry') }}</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at"
                               value="{{ $document->expires_at?->toDateString() }}">
                        <div class="form-text">{{ __('admin.document_expiry_hint') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="note">{{ __('admin.note') }}</label>
                        <textarea class="form-control" id="note" name="note" rows="3">{{ old('note', $document->review_note) }}</textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-success" name="verdict" value="conforme">
                            <i class="bi bi-check-lg me-1"></i>{{ __('admin.approve') }}
                        </button>
                        <button class="btn btn-outline-secondary" name="verdict" value="a_relever">
                            <i class="bi bi-clock me-1"></i>{{ __('admin.later') }}
                        </button>
                        <button class="btn btn-outline-danger" name="verdict" value="non_conforme">
                            <i class="bi bi-x-lg me-1"></i>{{ __('admin.reject') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection