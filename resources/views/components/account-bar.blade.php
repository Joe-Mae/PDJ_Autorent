{{--
    Barre de la page de profil, réservée au personnel connecté.

    Deux fonctions, volontairement réunies :

      1. revenir au profil depuis le formulaire de mot de passe, sans remonter
         la page ;
      2. se déconnecter, pour changer d'utilisateur sur une tablette de
         guichet partagée. Sans ce bouton visible, le second employé doit
         deviner qu'il faut passer par « Modifier mon profil ».

    La déconnexion est un POST et non un lien : un GET permettrait à un tiers
    de déconnecter un employé par une simple image glissée dans une page.
--}}
<div class="account-bar mb-4">
    <nav class="account-bar__links" aria-label="{{ __('account.my_profile') }}">
        <a class="account-bar__link {{ request()->routeIs('account.profile') ? 'is-active' : '' }}"
           href="{{ route('account.profile', ['locale' => $locale]) }}"
           @if (request()->routeIs('account.profile')) aria-current="page" @endif>
            <i class="bi bi-gear"></i>{{ __('account.edit_profile') }}
        </a>
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
