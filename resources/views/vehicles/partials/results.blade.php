{{-- Résultats de la recherche : rendus au chargement de la page puis
     remplacés en AJAX quand un filtre change. --}}
@if ($vehicles->isEmpty())
    <div class="pdj-card text-center py-5">
        <i class="bi bi-calendar-x fs-1 text-muted"></i>
        <h2 class="h5 fw-bold mt-3">{{ __('general.results_empty_title') }}</h2>
        <p class="text-muted">{{ __('general.results_empty_intro') }}</p>

        @if ($alternatives->isNotEmpty())
            <div class="row g-2 mt-2 text-start">
                @foreach ($alternatives as $row)
                    <div class="col-md-4">
                        <a class="d-block pdj-card h-100 text-decoration-none text-reset"
                           href="{{ route('vehicles.index', [
                               'locale' => $locale,
                               'du' => $row['next']->startsAtDate(),
                               'au' => $row['next']->endsAtDate(),
                               'heure_depart' => $row['next']->startsAtHour(),
                               'heure_retour' => $row['next']->endsAtHour(),
                           ]) }}">
                            <div class="fw-bold small">{{ $row['vehicle']->displayName() }}</div>
                            <div class="small text-muted">
                                {{ __('general.free_from', ['date' => $row['next']->startsAt->translatedFormat('D d M')]) }}
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <a class="btn btn-outline-primary" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
                {{ __('general.results_empty_all') }}
            </a>
            <a class="btn btn-accent"
               href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode(__('general.whatsapp_help')) }}"
               target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-1"></i>{{ __('general.results_empty_whatsapp') }}
            </a>
        </div>
    </div>
@else
    @include('vehicles.partials.rows', ['vehicles' => $vehicles, 'period' => $period])
@endif
