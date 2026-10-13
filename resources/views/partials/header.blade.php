@php
    /**
     * La bascule de langue doit produire une URL absolue : le préfixe /fr ou /en
     * est déjà dans le chemin. Partir d'un href relatif cassait le lien et
     * renvoyait vers une page qui n'existe pas.
     */
    $autreLangue = $locale === 'fr' ? 'en' : 'fr';
    $cheminCourant = ltrim(Str::after(request()->path(), $locale), '/');
    $urlBascule = url($autreLangue.($cheminCourant !== '' ? '/'.$cheminCourant : ''));
    $settings = app(\App\Services\SettingsService::class);
@endphp

<header class="pdj-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between py-2 gap-3">
            <a class="pdj-logo" href="{{ route('home', ['locale' => $locale]) }}">
                <span class="pdj-logo-mark">PDJ</span>
                <span>{{ $settings->string('site.brand_label') }}</span>
            </a>

            <nav class="d-none d-lg-flex align-items-center gap-4 pdj-nav">
                <a class="nav-link @if (request()->routeIs('home')) active @endif"
                   href="{{ route('home', ['locale' => $locale]) }}">{{ $settings->string('nav.home.'.$locale) }}</a>
                <a class="nav-link @if (request()->routeIs('vehicles.*') && ! request()->routeIs('vehicles.show')) active @endif"
                   href="{{ route('vehicles.index', ['locale' => $locale]) }}">{{ $settings->string('nav.fleet.'.$locale) }}</a>
                <a class="nav-link @if (request()->routeIs('page.driver')) active @endif"
                   href="{{ route('page.driver', ['locale' => $locale]) }}">{{ $settings->string('nav.driver.'.$locale) }}</a>
                <a class="nav-link @if (request()->routeIs('page.faq')) active @endif"
                   href="{{ route('page.faq', ['locale' => $locale]) }}">{{ $settings->string('nav.faq.'.$locale) }}</a>
                <a class="nav-link @if (request()->routeIs('page.contact')) active @endif"
                   href="{{ route('page.contact', ['locale' => $locale]) }}">{{ $settings->string('nav.contact.'.$locale) }}</a>
            </nav>

            <div class="d-flex align-items-center gap-2">
                <div class="locale-switch" role="group" aria-label="{{ __('nav.language') }}">
                    <a href="{{ $urlBascule }}"
                       class="{{ $locale === 'fr' ? 'active' : '' }}"
                       hreflang="fr"
                       lang="fr"
                       @if ($locale === 'fr') aria-current="true" @endif>FR</a>
                    <a href="{{ $urlBascule }}"
                       class="{{ $locale === 'en' ? 'active' : '' }}"
                       hreflang="en"
                       lang="en"
                       @if ($locale === 'en') aria-current="true" @endif>EN</a>
                </div>

                @auth
                    <a class="btn btn-outline-primary d-none d-sm-inline-flex align-items-center"
                       href="{{ route('account.dashboard', ['locale' => $locale]) }}">
                        {{ __('nav.my_account') }}
                    </a>

                    {{-- La déconnexion reste atteignable depuis n'importe quelle
                         page, pas seulement de « Mon espace ». Sur un téléphone
                         prêté au guichet, le client suivant arrive souvent sur
                         la page d'accueil : sans ce bouton, il n'a aucun moyen
                         de passer la main. Icône seule pour ne pas alourdir une
                         barre déjà chargée ; le libellé complet attend dans le
                         menu mobile et dans la barre de l'espace client. --}}
                    <form action="{{ route('logout', ['locale' => $locale]) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger d-none d-sm-inline-flex align-items-center"
                                aria-label="{{ __('nav.logout') }}" title="{{ __('nav.logout') }}">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                @else
                    <a class="btn btn-outline-primary d-none d-sm-inline-flex align-items-center"
                       href="{{ route('login', ['locale' => $locale]) }}">
                        {{ __('nav.login') }}
                    </a>
                @endauth

                <button class="btn btn-primary d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#menuMobile"
                        aria-controls="menuMobile" aria-label="{{ __('nav.menu') }}">
                    <i class="bi bi-list fs-5"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-end" tabindex="-1" id="menuMobile" aria-labelledby="menuMobileLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="menuMobileLabel">{{ __('nav.menu') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('nav.close') }}"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-1">
        <a class="nav-link py-2" href="{{ route('home', ['locale' => $locale]) }}">{{ $settings->string('nav.home.'.$locale) }}</a>
        <a class="btn btn-primary btn-lg mb-2" href="{{ route('vehicles.index', ['locale' => $locale]) }}">
            {{ $settings->string('nav.fleet.'.$locale) }}
        </a>
        <a class="nav-link py-2" href="{{ route('page.driver', ['locale' => $locale]) }}">{{ $settings->string('nav.driver.'.$locale) }}</a>
        <a class="nav-link py-2" href="{{ route('page.faq', ['locale' => $locale]) }}">{{ $settings->string('nav.faq.'.$locale) }}</a>
        <a class="nav-link py-2" href="{{ route('page.payment', ['locale' => $locale]) }}">{{ __('nav.payment') }}</a>
        <a class="nav-link py-2" href="{{ route('page.deposit', ['locale' => $locale]) }}">{{ __('nav.deposit') }}</a>
        <a class="nav-link py-2" href="{{ route('page.contact', ['locale' => $locale]) }}">{{ $settings->string('nav.contact.'.$locale) }}</a>

        <hr class="my-3">

        @auth
            <a class="btn btn-outline-primary" href="{{ route('account.dashboard', ['locale' => $locale]) }}">
                {{ __('nav.my_account') }}
            </a>

            <form action="{{ route('logout', ['locale' => $locale]) }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 d-inline-flex justify-content-center align-items-center gap-2">
                    <i class="bi bi-box-arrow-right"></i>{{ __('nav.logout') }}
                </button>
            </form>
        @else
            <a class="btn btn-outline-primary" href="{{ route('login', ['locale' => $locale]) }}">
                {{ __('nav.login') }}
            </a>
        @endauth
    </div>
</div>
