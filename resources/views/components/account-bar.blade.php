{{--
    Barre de session de l'espace client.

    Deux fonctions, volontairement réunies :

      1. se déplacer entre Mon espace, Réservations, Documents et Profil — sans
         elle, on ne revient qu'en passant par l'en-tête, ce qui oblige à
         remonter la page ;
      2. se déconnecter, pour changer d'utilisateur sur un téléphone partagé.
         Au guichet comme à la maison, un même appareil sert souvent à deux
         personnes : sans ce bouton visible, le second client doit deviner
         qu'il faut passer par « Modifier mon profil ».

    La déconnexion est un POST et non un lien : un GET permettrait à un tiers de
    déconnecter le client par une simple image glissée dans une page.
--}}
@php
    $onglets = [
        ['route' => 'account.dashboard', 'label' => __('nav.my_account'), 'icon' => 'person'],
        ['route' => 'account.bookings', 'label' => __('account.my_bookings'), 'icon' => 'calendar-check'],
        ['route' => 'account.documents', 'label' => __('account.my_documents'), 'icon' => 'file-earmark-text'],
        ['route' => 'account.profile', 'label' => __('account.edit_profile'), 'icon' => 'gear'],
    ];
@endphp

<div class="account-bar mb-4">
    <nav class="account-bar__links" aria-label="{{ __('nav.my_account') }}">
        @foreach ($onglets as $onglet)
            <a class="account-bar__link {{ request()->routeIs($onglet['route']) ? 'is-active' : '' }}"
               href="{{ route($onglet['route'], ['locale' => $locale]) }}"
               @if (request()->routeIs($onglet['route'])) aria-current="page" @endif>
                <i class="bi bi-{{ $onglet['icon'] }}"></i>{{ $onglet['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- La déconnexion vit dans son propre formulaire : un POST avec jeton,
         jamais un lien. --}}
    <form action="{{ route('logout', ['locale' => $locale]) }}" method="POST" class="account-bar__logout">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-right"></i>{{ __('nav.logout') }}
        </button>
    </form>
</div>
