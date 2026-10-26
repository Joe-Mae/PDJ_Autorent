@extends('layouts.panel')

@section('title', 'Bloc chauffeur')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Bloc chauffeur de l’accueil</h1>
            <p class="text-muted small mb-0">Badge « Options premium », arguments, mariages et bouton. Les tarifs se règlent dans les paramètres de l’agence.</p>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.index') }}">{{ __('admin.back') }}</a>
    </div>

    @php
        $fields = [
            ['premium_badge', 'Badge', 'text', 'Petit libellé affiché au-dessus du titre.'],
            ['title', 'Titre', 'text', null],
            ['intro', 'Introduction', 'textarea', null],
            ['point1', 'Argument 1', 'textarea', 'Utilisez :price pour insérer le tarif avec voiture.'],
            ['point2', 'Argument 2', 'textarea', null],
            ['point3', 'Argument 3', 'textarea', 'Par exemple les mariages et les grandes occasions.'],
            ['point4', 'Argument 4', 'textarea', null],
            ['cta', 'Bouton', 'text', null],
            ['page_intro', 'Introduction de la page chauffeur', 'textarea', 'Affichée en haut de la page « Location avec chauffeur ».'],
        ];
    @endphp

    <form method="POST" action="{{ route('admin.contents.driver.update') }}">
        @csrf

        <div class="pdj-card">
            <p class="small text-muted mb-0">Un champ vidé réaffiche le texte d’origine. Le site est bilingue : remplissez le français et l’anglais.</p>

            @foreach ($fields as [$field, $label, $type, $hint])
                <div class="{{ $loop->first ? 'mt-3' : 'border-top pt-3 mt-3' }}">
                    <div class="fw-semibold">{{ $label }}</div>
                    @if ($hint)
                        <div class="small text-muted mb-2">{{ $hint }}</div>
                    @endif

                    <div class="row g-3">
                        @foreach (['fr' => 'Français', 'en' => 'English'] as $locale => $localeLabel)
                            <div class="col-md-6">
                                <label class="form-label small" for="{{ $field }}_{{ $locale }}">{{ $localeLabel }}</label>

                                @if ($type === 'textarea')
                                    <textarea class="form-control @error($field.'_'.$locale) is-invalid @enderror"
                                              id="{{ $field }}_{{ $locale }}"
                                              name="{{ $field }}_{{ $locale }}"
                                              rows="3">{{ old($field.'_'.$locale, $values[$field][$locale]) }}</textarea>
                                @else
                                    <input type="text"
                                           class="form-control @error($field.'_'.$locale) is-invalid @enderror"
                                           id="{{ $field }}_{{ $locale }}"
                                           name="{{ $field }}_{{ $locale }}"
                                           value="{{ old($field.'_'.$locale, $values[$field][$locale]) }}">
                                @endif

                                @error($field.'_'.$locale)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-primary">{{ __('admin.save') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.contents.index') }}">{{ __('admin.cancel') }}</a>
            <a class="btn btn-outline-secondary ms-auto" target="_blank" rel="noopener"
               href="{{ route('home', ['locale' => 'fr']) }}">
                Voir l’accueil <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
            <a class="btn btn-outline-secondary" target="_blank" rel="noopener"
               href="{{ route('page.driver', ['locale' => 'fr']) }}">
                Voir la page chauffeur <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        </div>
    </form>
@endsection
