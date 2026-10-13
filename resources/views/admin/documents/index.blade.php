@extends('layouts.panel')

@section('title', 'VÃ©rification des documents')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">
            {{ __('admin.documents_queue') }}
            <span class="badge bg-warning text-dark">{{ $stats['pending'] }}</span>
        </h1>
        <span class="small text-muted">{{ $stats['today'] }} {{ __('admin.reviewed_today') }}</span>
    </div>

    <p class="small text-muted">{{ __('admin.documents_queue_hint') }}</p>

    <div class="pdj-card p-0 overflow-hidden">
        <table class="table admin-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('admin.departure') }}</th>
                    <th>{{ __('admin.client') }}</th>
                    <th>{{ __('admin.type') }}</th>
                    <th>{{ __('admin.booking') }}</th>
                    <th>{{ __('admin.received_at') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td class="small">
                            @if ($document->booking)
                                {{ $document->booking->starts_at->translatedFormat('D d M') }}
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ $document->user?->displayName() }}<br>
                            <a class="text-muted" href="tel:{{ $document->user?->phone }}">{{ $document->user?->phone }}</a>
                        </td>
                        <td class="small fw-semibold">{{ $document->type->label() }}</td>
                        <td class="small">
                            @if ($document->booking)
                                <a href="{{ route('admin.bookings.show', $document->booking) }}">{{ $document->booking->reference }}</a>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $document->created_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.documents.show', $document) }}">
                                {{ __('admin.verify') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('admin.queue_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $documents->links() }}</div>
@endsection