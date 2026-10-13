<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La langue vit dans l'URL, pas dans un cookie.
 *
 * Conséquence : un lien partagé par un client s'ouvre dans la langue du lien,
 * et le bouton de bascule change réellement d'adresse. Le cookie ne sert
 * qu'à proposer la bonne langue au premier visitEUR.
 */
class SetLocale
{
    public const SUPPORTED = ['fr', 'en'];

    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $this->guess($request);
        }

        app()->setLocale($locale);
        session([self::SESSION_KEY => $locale]);

        return $next($request);
    }

    /** Langue du visiteur : session, puis en-tête du navigateur, puis français. */
    private function guess(Request $request): string
    {
        $fromSession = session(self::SESSION_KEY);

        if (in_array($fromSession, self::SUPPORTED, true)) {
            return $fromSession;
        }

        $preferred = $this->preferredFromHeader((string) $request->header('Accept-Language'));

        return $preferred ?? 'fr';
    }

    private function preferredFromHeader(string $header): ?string
    {
        foreach (explode(',', $header) as $chunk) {
            $code = strtolower(trim(explode(';', $chunk)[0]));

            if (str_starts_with($code, 'en')) {
                return 'en';
            }

            if (str_starts_with($code, 'fr')) {
                return 'fr';
            }
        }

        return null;
    }
}
