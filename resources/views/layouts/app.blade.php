@php
    /**
     * URL absolue de la même page dans l'autre langue. Le chemin contient déjà
     * le préfixe /fr ou /en : on le remplace, on ne le préfixe pas une seconde
     * fois, sinon le lien devient /en/fr/... et renvoie un 404.
     */
    $locale = app()->getLocale();
    $settings = app(\App\Services\SettingsService::class);
    $siteBrandColor = $settings->string('site.brand_color');
    $siteAccentColor = $settings->string('site.accent_color');
    $siteBrandColor = preg_match('/\A#[0-9A-Fa-f]{6}\z/', $siteBrandColor) === 1 ? $siteBrandColor : '#0A6E4C';
    $siteAccentColor = preg_match('/\A#[0-9A-Fa-f]{6}\z/', $siteAccentColor) === 1 ? $siteAccentColor : '#FFC400';
    $autreLangue = $locale === 'fr' ? 'en' : 'fr';
    $cheminCourant = ltrim(Str::after(request()->path(), $locale), '/');
    $urlBascule = url($autreLangue.($cheminCourant !== '' ? '/'.$cheminCourant : ''));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Le message des champs obligatoires est posé par le JavaScript ; il est
         transmis ici parce que les fichiers de langue sont du PHP, pas du JS. --}}
    <script>window.PDJ = { requiredMessage: @json(__('general.required_field')) };</script>
    <title>@yield('title', config('app.name')) — {{ __('general.tagline', ['city' => 'Lomé']) }}</title>
    <meta name="description" content="@yield('description', __('general.meta_description'))">

    {{-- Chaque page se déclare dans les deux langues : un lien partagé par WhatsApp
         doit s'ouvrir correctement chez le destinataire, quelle que soit sa langue. --}}
    <link rel="alternate" hreflang="fr" href="{{ url('fr'.($cheminCourant !== '' ? '/'.$cheminCourant : '')) }}">
    <link rel="alternate" hreflang="en" href="{{ $urlBascule }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('fr'.($cheminCourant !== '' ? '/'.$cheminCourant : '')) }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Outfit pour les titres, Inter pour le texte. `display=swap` et une
         feuille non bloquante : si le réseau est lent ou coupé, le site
         s'affiche immédiatement avec la police du système. La typographie
         seule ne doit jamais empêcher un client de réserver. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" media="print" onload="this.media='all'"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap">
    </noscript>

    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body @class([
    'public-site' => ! request()->routeIs('account.*', 'admin.*'),
    'structured-pages' => ! request()->routeIs('account.dashboard', 'vehicles.index'),
]) @if (! request()->routeIs('account.*', 'admin.*')) style="--pdj-site-brand: {{ $siteBrandColor }}; --pdj-site-accent: {{ $siteAccentColor }};" @endif>

@include('partials.header')

<main>
    @if (session('status'))
        <div class="container py-3">
            <div class="alert alert-success mb-0 d-flex align-items-center gap-2" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="container py-3">
            @if (session('error'))
                <div class="alert alert-danger mb-0 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-0" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @yield('content')
</main>

@include('partials.footer')

{{-- WhatsApp reste accessible partout, mais on ne le montre pas dans le tunnel :
     un client qui appelle pendant qu'il remplit le formulaire n'est plus là pour le remplir. --}}
@if (! request()->routeIs('booking.*') && ! request()->routeIs('phone.*'))
    <a class="whatsapp-fab"
       href="https://wa.me/{{ config('pdj.whatsapp') }}?text={{ rawurlencode(__('general.whatsapp_help')) }}"
       target="_blank" rel="noopener"
       aria-label="{{ __('general.contact_whatsapp') }}"
       data-bs-toggle="tooltip" title="{{ __('general.contact_whatsapp') }}">
        <i class="bi bi-whatsapp fs-4"></i>
    </a>
@endif

@stack('scripts')

</body>
</html>
