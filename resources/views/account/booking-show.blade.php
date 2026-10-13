@extends('layouts.app')

@section('title', $booking->reference)

@section('content')
    <div class="container py-4" style="max-width: 760px;">

    <x-account-bar />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">{{ $booking->reference }}</h1>
            <span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span>
        </div>

        @if ($booking->status_reason)
            <div class="alert alert-warning">{{ $booking->status_reason }}</div>
        @endif

        <div class="pdj-card mb-3">
            <div class="d-flex gap-3">
                <img src="{{ $booking->vehicle->mainPhotoUrl() ?: asset('images/vehicle-placeholder.svg') }}"
                     class="rounded" width="120" height="84" style="object-fit: cover;" alt="">
                <div>
                    <div class="fw-bold">{{ $booking->vehicle->fullName() }}</div>
                    <div class="small text-muted">
                        {{ $booking->starts_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $booking->pickup_hour) }}
                        <i class="bi bi-arrow-right mx-1"></i>
                        {{ $booking->ends_at->translatedFormat('D d M') }} {{ sprintf('%02dh00', $booking->return_hour) }}
                    </div>
                    <div class="small text-muted">{{ $booking->days }} {{ __('general.days') }}</div>
                </div>
            </div>
        </div>

        <x-price-ticket :quote="$quote" class="mb-3" />

        <div class="pdj-card mb-3">
            <div class="section-title">{{ __('admin.documents') }}</div>
            @forelse ($booking->documents as $document)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span class="small">{{ $document->type->label() }}</span>
                    <span class="badge bg-{{ $document->status->color() }}">{{ $document->status->label() }}</span>
                </div>
            @empty
                <p class="small text-muted mb-0">{{ __('account.no_document') }}</p>
            @endforelse
        </div>

        @if ($booking->payments->isNotEmpty())
            <div class="pdj-card mb-3">
                <div class="section-title">{{ __('admin.payments') }}</div>
                <table class="table admin-table mb-0">
                    <tbody>
                        @foreach ($booking->payments as $payment)
                            <tr>
                                <td class="small">{{ $payment->kind->label() }} w {{ $payment->method->label() }}</td>
                                <td class="text-end small">{{ number_format($payment->amount, 0, ' ', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($booking->status === \App\Enums\BookingStatus::Terminee && $booking->review === null)
            <div class="pdj-card mb-3">
                <div class="section-title">{{ __('account.leave_review') }}</div>
                <form action="{{ route('reviews.store', ['locale' => $locale]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <div class="mb-2">
                        <label class="form-label" for="rating">{{ __('admin.rating') }}</label>
                        <select class="form-select" name="rating" id="rating" required>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ str_repeat('â˜…', $i) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="comment">{{ __('admin.comment') }}</label>
                        <textarea class="form-control" name="comment" id="comment" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary w-100">{{ __('account.send_review') }}</button>
                </form>
            </div>
        @endif

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('account.bookings', ['locale' => $locale]) }}">
                {{ __('admin.back') }}
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('booking.receipt', ['locale' => $locale, 'booking' => $booking]) }}">
                {{ __('booking.download_receipt') }}
            </a>
        </div>
    </div>
@endsection
