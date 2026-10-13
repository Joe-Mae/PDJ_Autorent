@extends('layouts.app')

@section('title', __('account.my_documents'))

@section('content')
    <div class="container py-4" style="max-width: 760px;">

    <x-account-bar />
        <h1 class="h4 fw-bold mb-1">{{ __('account.my_documents') }}</h1>
        <p class="text-muted mb-4">{{ __('account.documents_intro') }}</p>

        @foreach ($reusable as $type => $documents)
            <div class="pdj-card mb-3">
                <div class="section-title">{{ \App\Enums\DocumentType::from($type)->label() }}</div>
                @foreach ($documents as $document)
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">
                            <i class="bi bi-patch-check-fill text-success me-1"></i>
                            {{ __('document.upload_reuse', ['date' => $document->reviewed_at?->translatedFormat('d/m/Y') ?? 'â€”']) }}
                        </span>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="{{ route('account.documents.show', ['locale' => $locale, 'document' => $document]) }}">
                            {{ __('account.download') }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endforeach

        <h2 class="h6 fw-bold mt-4 mb-2">{{ __('account.history') }}</h2>
        <div class="pdj-card p-0 overflow-hidden">
            <table class="table admin-table mb-0">
                <tbody>
                    @forelse ($history as $document)
                        <tr>
                            <td class="small">{{ $document->type->label() }}</td>
                            <td class="small text-muted">{{ $document->created_at->translatedFormat('d/m/Y') }}</td>
                            <td><span class="badge bg-{{ $document->status->color() }}">{{ $document->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="text-muted small">{{ __('account.no_document') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $history->links() }}</div>
    </div>
@endsection
