<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barrière de l'administration.
 *
 * Un compte client ne doit jamais atteindre /admin, même avec une URL devinée.
 * Le rôle est lu via spatie/laravel-permission : Super admin, Gestionnaire,
 * Agent d'accueil.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest($this->loginUrl());
        }

        if (! $user->isStaff()) {
            abort(403, __('auth.forbidden'));
        }

        return $next($request);
    }

    /**
     * La route de connexion vit sous le préfixe /fr ou /en. Les écrans
     * d'administration sont, eux, hors de ce préfixe : appeler route('login')
     * sans langue levait une UrlGenerationException, et un employé déconnecté
     * qui ouvrait /admin obtenait une erreur 500 au lieu du formulaire.
     */
    private function loginUrl(): string
    {
        $locale = session(SetLocale::SESSION_KEY);

        return route('login', [
            'locale' => in_array($locale, SetLocale::SUPPORTED, true) ? $locale : app()->getLocale(),
        ]);
    }
}
