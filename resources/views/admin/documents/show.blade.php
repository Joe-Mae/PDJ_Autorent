@extends('layouts.panel')

@section('title', 'Vérifier : '.$document->type->label())

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
                @if ($document->mime_type === 'application/pdf')
                    <div class="border rounded p-4 text-center">
                        <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                        <div class="mt-3">
                            <a class="btn btn-outline-primary" href="{{ $document->temporaryUrl() }}"
                               target="_blank" rel="noopener">{{ __('admin.open_document') }}</a>
                        </div>
                    </div>
                @else
                    <a href="{{ $document->temporaryUrl() }}" target="_blank" rel="noopener">
                        <img src="{{ $document->temporaryUrl() }}" alt="{{ $document->type->label() }}"
                             class="img-fluid rounded" style="max-height: 70vh;">
                    </a>
                @endif
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
                        @if ($document->user)
                            <tr><td>{{ __('booking.field_first_name') }}</td><td class="text-end fw-semibold">{{ $document->user->first_name }}</td></tr>
                            <tr><td>{{ __('booking.field_last_name') }}</td><td class="text-end fw-semibold">{{ $document->user->last_name }}</td></tr>
                            <tr><td>{{ __('booking.field_birth_date') }}</td><td class="text-end">{{ $document->user->birth_date?->format('d/m/Y') }}</td></tr>
                            <tr><td>{{ __('booking.field_phone') }}</td><td class="text-end">{{ $document->user->phone }}</td></tr>
                        @elseif ($document->booking)
                            <tr><td>{{ __('admin.client') }}</td><td class="text-end fw-semibold">{{ $document->booking->customer_name }}</td></tr>
                            <tr><td>{{ __('booking.field_phone') }}</td><td class="text-end">{{ $document->booking->customer_phone }}</td></tr>
                            <tr><td>{{ __('booking.field_email') }}</td><td class="text-end">{{ $document->booking->customer_email }}</td></tr>
                        @elseif ($document->driverOnlyBooking)
                            <tr><td>{{ __('admin.client') }}</td><td class="text-end fw-semibold">{{ $document->driverOnlyBooking->customer_name }}</td></tr>
                            <tr><td>{{ __('booking.field_phone') }}</td><td class="text-end">{{ $document->driverOnlyBooking->phone }}</td></tr>
                            <tr><td>{{ __('booking.guest.period') }}</td><td class="text-end">
                                {{ $document->driverOnlyBooking->starts_at->translatedFormat('d/m/Y') }} →
                                {{ $document->driverOnlyBooking->ends_at->translatedFormat('d/m/Y') }}
                            </td></tr>
                        @endif
                        @if ($document->booking)
                            <tr><td>{{ __('admin.reservation') }}</td><td class="text-end">
                                <a href="{{ route('admin.bookings.show', $document->booking) }}">{{ $document->booking->reference }}</a>
                            </td></tr>
                            <tr><td>{{ __('admin.vehicle') }}</td><td class="text-end">{{ $document->booking->vehicle->displayName() }}</td></tr>
                        @elseif ($document->driverOnlyBooking)
                            <tr><td>{{ __('admin.reservation') }}</td><td class="text-end">
                                <a href="{{ route('admin.driver-only-bookings.index', ['q' => $document->driverOnlyBooking->reference]) }}">
                                    {{ $document->driverOnlyBooking->reference }}
                                </a><br>
                                <span class="badge bg-secondary">{{ __('driver.solo_title') }}</span>
                            </td></tr>
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

            <div class="pdj-card mt-4">
                <h2 class="h6 fw-bold mb-1">{{ __('admin.access_history') }}</h2>
                <p class="small text-muted mb-3">{{ __('admin.access_history_hint') }}</p>

                @if ($history->isEmpty())
                    <p class="small text-muted mb-0">{{ __('admin.access_history_empty') }}</p>
                @else
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($history as $entry)
                            <li class="d-flex justify-content-between gap-2 py-1 border-bottom border-light">
                                <span>
                                    <i class="bi bi-{{ $entry->event === 'document.reviewed' ? 'stamp' : 'eye' }} me-1 text-muted"></i>
                                    {{ $entry->actor?->displayName() ?? __('admin.system') }}
                                    {{ $entry->event === 'document.reviewed' ? __('admin.reviewed') : __('admin.accessed') }}
                                </span>
                                <span class="text-nowrap text-muted">{{ $entry->created_at->translatedFormat('d/m H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection