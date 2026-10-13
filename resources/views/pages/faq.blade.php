@extends('pages.layout')

@section('title', __('nav.faq'))

@section('pageContent')

    <h1 class="h3 fw-bold mb-2">{{ __('nav.faq') }}</h1>
    <p class="lead text-muted mb-4">{{ __('page.faq_intro') }}</p>

    @forelse ($faqs as $category => $items)
        <h2 class="h5 fw-bold mt-4 mb-2">{{ $category }}</h2>
        <div class="accordion mb-3" id="faq-{{ \Illuminate\Support\Str::slug($category) }}">
            @foreach ($items as $item)
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#q-{{ $item->id }}" aria-expanded="false">
                            {{ $item->{'question_'.$locale} ?: $item->question_fr }}
                        </button>
                    </h3>
                    <div id="q-{{ $item->id }}" class="accordion-collapse collapse">
                        <div class="accordion-body small">
                            {!! nl2br(e($item->{'answer_'.$locale} ?: $item->answer_fr)) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <p class="text-muted">{{ __('page.faq_empty') }}</p>
    @endforelse

    <div class="pdj-card mt-4 text-center">
        <p class="mb-3">{{ __('page.faq_more') }}</p>
        <a class="btn btn-accent" href="https://wa.me/{{ app(\App\Services\SettingsService::class)->whatsappNumber() }}"
           target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-1"></i>{{ __('general.contact_whatsapp') }}
        </a>
    </div>

@endsection
