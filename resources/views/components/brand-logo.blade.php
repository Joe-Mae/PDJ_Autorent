@use('Illuminate\Support\Facades\Storage')

@php
    /**
     * Marque de l'agence : le logo téléversé depuis /admin/apparence s'il en
     * existe un, sinon le monogramme saisi dans les réglages. Aucune des deux
     * valeurs n'est écrite en dur ici — c'est le seul point d'affichage de la
     * marque, appelé par l'en-tête, le pied de page et le panneau d'admin.
     *
     * Le chemin est contrôlé avant usage : jamais de « .. », et le fichier
     * doit réellement exister sur le disque public.
     */
    $settings = app(\App\Services\SettingsService::class);
    $logo = $settings->string('site.logo_path');
    $hasLogo = $logo !== ''
        && ! str_contains($logo, '..')
        && Storage::disk('public')->exists($logo);
@endphp

@if ($hasLogo)
    <img src="{{ Storage::disk('public')->url($logo) }}"
         alt="{{ $settings->string('site.brand_label') }}"
         {{ $attributes->merge(['class' => 'pdj-logo-img']) }}>
@else
    <span {{ $attributes->merge(['class' => 'pdj-logo-mark']) }}>{{ $settings->string('site.brand_mark') }}</span>
@endif
