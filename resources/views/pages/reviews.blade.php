@extends('pages.layout')

@section('title', __('general.reviews_title'))

@section('pageContent')
    <h1 class="h3 fw-bold mb-2">{{ __('general.reviews_title') }}</h1>

    @if ($total > 0)
        <p class="lead text-muted mb-4">
            <span class="rating-stars fs-5">{{ str_repeat('★', (int) round($average)) }}</span>
            {{ number_format($average, 1) }} · {{ $total }} {{ __('general.reviews_count') }}
        </p>

        <div class="row g-3">
            @foreach ($reviews as $review)
                <div class="col-md-6">
                    <div class="pdj-card h-100">
                        <div class="rating-stars mb-2">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                        <p class="small mb-2">{{ $review->comment }}</p>
                        <div class="small text-muted">
                            <strong>{{ $review->author_name }}</strong>
                            @if ($review->author_city) — {{ $review->author_city }} @endif
                            · {{ $review->created_at->translatedFormat('M Y') }}
                        </div>
                        @if ($review->hasAnswer())
                            <div class="quote-block small text-muted mt-2">
                                <strong>{{ __('general.agency_answer') }}</strong><br>{{ $review->agency_answer }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $reviews->links() }}</div>
    @else
        <p class="text-muted">{{ __('general.no_reviews_yet') }}</p>
    @endif
@endsection
