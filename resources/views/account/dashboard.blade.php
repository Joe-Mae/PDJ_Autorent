@extends('layouts.app')

@section('title', __('nav.my_account'))

@section('content')

<div class="container py-4" style="max-width: 900px;">

    <x-account-bar />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">{{ __('account.hello', ['name' => $user->first_name]) }}</h1>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
            {{ __('general.search_cta') }}
        </a>
    </div>

    {{-- ── En cours ─────────────────────────────────────────────────────── --}}
    @if ($ongoing)
        <div class="pdj-card mb-3" style="border-left: 4px solid var(--pdj-green);">
            <span class="badge bg-primary">{{ $ongoing->status->label() }}</span>
            <h2 class="h6 fw-bold mt-2">{{ $ongoing->vehicle->fullName() }}</h2>
            <p class="small text-muted mb-2">
                <i class="bi bi-calendar3 me-1"></i>
                {{ $ongoing->starts_at->translatedFormat('D d M H:i') }}
                <i class="bi bi-arrow-right mx-1"></i>
                {{ $ongoing->ends_at->translatedFormat('D d M H:i') }}
            </p>
            @if (! $ongoing->isFullyPaid())
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ __('account.outstanding', [
                        'amount' => number_format($ongoing->price_total - $ongoing->rentalPaid(), 0, ' ', ' '),
                    ]) }}
                </div>
            @endif
            <a class="btn btn-sm btn-primary mt-3"
               href="{{ route('account.bookings.show', ['locale' => $locale, 'booking' => $ongoing]) }}">
                {{ __('account.see_details') }}
            </a>
        </div>
    @endif

    {{-- ── Prochaine réservation ─────────────────────────────────────────── --}}
    @if ($upcoming)
        @php $days = max(0, (int) now()->startOfDay()->diffInDays($upcoming->starts_at->startOfDay(), false)) @endphp

        <div class="pdj-card mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-{{ $upcoming->status->color() }}">{{ $upcoming->status->label() }}</span>
                @if ($days <= 1)
                    <span class="badge bg-warning text-dark">{{ __('account.in_days', ['days' => $days]) }}</span>
                @endif
            </div>

            <h2 class="h6 fw-bold">{{ $upcoming->vehicle->fullName() }}</h2>
            <p class="small text-muted mb-3">
                {{ $upcoming->starts_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $upcoming->pickup_hour) }}
                <i class="bi bi-arrow-right mx-1"></i>
                {{ $upcoming->ends_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $upcoming->return_hour) }}
            </p>

            @if ($missingDocuments !== [])
                <div class="alert alert-warning small">
                    <strong>{{ __('booking.missing_documents') }}</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($missingDocuments as $type)
                            <li>{{ \App\Enums\DocumentType::from($type)->label() }}</li>
                        @endforeach
                    </ul>
                    <a class="btn btn-sm btn-primary mt-2"
                       href="{{ route('booking.documents', ['locale' => $locale, 'booking' => $upcoming]) }}">
                        {{ __('booking.back_to_documents') }}
                    </a>
                </div>
            @else
                <div class="alert alert-success small mb-3">
                    <i class="bi bi-check-circle me-1"></i>{{ __('account.documents_ok') }}
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-primary"
                   href="{{ route('account.bookings.show', ['locale' => $locale, 'booking' => $upcoming]) }}">
                    {{ __('account.see_details') }}
                </a>
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('booking.receipt', ['locale' => $locale, 'booking' => $upcoming]) }}">
                    {{ __('booking.download_receipt') }}
                </a>

                @if ($upcoming->status->canBeCancelled())
                    <form action="{{ route('account.bookings.cancel', ['locale' => $locale, 'booking' => $upcoming]) }}"
                          method="POST" onsubmit="return confirm('{{ __('booking.cancel_confirm') }}')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            {{ __('booking.cancel_free') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="pdj-card text-center py-4 mb-3">
            <i class="bi bi-calendar-plus fs-2 text-muted"></i>
            <p class="mb-3">{{ __('account.no_upcoming') }}</p>
            <a class="btn btn-primary" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
                {{ __('general.search_cta') }}
            </a>
        </div>
    @endif

    {{-- ── Historique ───────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-baseline mt-4 mb-2">
        <h2 class="h6 fw-bold mb-0">{{ __('account.history') }}</h2>
        <a class="small" href="{{ route('account.bookings', ['locale' => $locale]) }}">{{ __('account.see_all') }}</a>
    </div>

    @forelse ($recent as $booking)
        <a class="d-flex justify-content-between align-items-center pdj-card mb-2 text-decoration-none text-reset"
           href="{{ route('account.bookings.show', ['locale' => $locale, 'booking' => $booking]) }}">
            <div>
                <div class="fw-semibold">{{ $booking->vehicle->displayName() }}</div>
                <div class="small text-muted">{{ $booking->starts_at->translatedFormat('d M Y') }}</div>
            </div>
            <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
        </a>
    @empty
        <p class="text-muted small">{{ __('account.no_history') }}</p>
    @endforelse
</div>

@endsection
