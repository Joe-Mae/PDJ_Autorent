@php
    /**
     * Même règle que dans l'en-tête : le chemin contient déjà le préfixe de
     * langue, on le remplace plutôt que de l'ajouter par-dessus.
     */
    $autreLangue = $locale === 'fr' ? 'en' : 'fr';
    $cheminCourant = ltrim(Str::after(request()->path(), $locale), '/');
    $urlBascule = url($autreLangue.($cheminCourant !== '' ? '/'.$cheminCourant : ''));
    $settings = app(\App\Services\SettingsService::class);
@endphp

<footer class="pdj-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="pdj-logo text-white mb-3" href="{{ route('home', ['locale' => $locale]) }}">
                    <span class="pdj-logo-mark">{{ $settings->string('site.brand_mark') }}</span>
                    <span>{{ $settings->string('site.brand_label') }}</span>
                </a>
                <p class="mb-3" style="max-width: 34ch;">{{ $settings->string('site.footer_tagline.'.$locale) }}</p>
                <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $settings->string('agency.address') }}</p>
                <p class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $settings->formattedPhone() }}</p>
                <p class="mb-0"><i class="bi bi-clock me-2"></i>{{ __('general.opening_hours') }}</p>
            </div>

            <div class="col-6 col-lg-2">
                <h5>{{ __('general.footer_explore') }}</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('vehicles.index', ['locale' => $locale]) }}">{{ $settings->string('nav.fleet.'.$locale) }}</a></li>
                    <li><a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => 'suv-4x4']) }}">SUV / 4x4</a></li>
                    <li><a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => 'citadines']) }}">{{ __('nav.citadines') }}</a></li>
                    <li><a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => 'luxe']) }}">{{ __('nav.luxe') }}</a></li>
                    <li><a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => 'utilitaires']) }}">{{ __('nav.utilitaires') }}</a></li>
                    <li><a href="{{ route('vehicles.category', ['locale' => $locale, 'category' => 'electriques']) }}">{{ __('nav.electriques') }}</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-3">
                <h5>{{ __('general.footer_help') }}</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('page.faq', ['locale' => $locale]) }}">{{ $settings->string('nav.faq.'.$locale) }}</a></li>
                    <li><a href="{{ route('page.payment', ['locale' => $locale]) }}">{{ __('nav.payment') }}</a></li>
                    <li><a href="{{ route('page.deposit', ['locale' => $locale]) }}">{{ __('nav.deposit') }}</a></li>
                    <li><a href="{{ route('page.insurance', ['locale' => $locale]) }}">{{ __('nav.insurance') }}</a></li>
                    <li><a href="{{ route('page.driver', ['locale' => $locale]) }}">{{ $settings->string('nav.driver.'.$locale) }}</a></li>
                    <li><a href="{{ route('page.terms', ['locale' => $locale]) }}">{{ __('nav.terms') }}</a></li>
                </ul>
            </div>

            <div class="col-lg-3">
                <h5>{{ $settings->string('nav.contact.'.$locale) }}</h5>
                <a class="d-inline-flex align-items-center gap-2 mb-2"
                   href="https://wa.me/{{ $settings->whatsappNumber() }}"
                   target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
                <p class="mb-2">{{ __('general.contact_whatsapp') }}, 24h/24.</p>
                <p class="mb-0">{{ __('general.no_online_payment') }}</p>
            </div>
        </div>

        <div class="bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <span>© {{ now()->year }} {{ $settings->string('agency.name') }}. {{ __('general.footer_rights') }}</span>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('page.privacy', ['locale' => $locale]) }}">{{ __('nav.privacy') }}</a>
                <a href="{{ route('page.legal', ['locale' => $locale]) }}">{{ __('nav.legal') }}</a>
                <a class="admin-footer-link"
                   href="{{ route('admin.dashboard') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="{{ __('general.admin_access') }}">{{ __('general.admin_access') }}</a>
                <span class="locale-switch">
                    <a href="{{ $urlBascule }}" lang="fr" @if ($locale === 'fr') aria-current="true" @endif>FR</a>
                    <a href="{{ $urlBascule }}" lang="en" @if ($locale === 'en') aria-current="true" @endif>EN</a>
                </span>
            </div>
        </div>
    </div>
</footer>
