<?php

namespace App\Http\Middleware;

use App\Support\StaffTier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barrière de périmètre à l'intérieur de l'administration.
 *
 * EnsureStaff répond à « est-ce un employé ? ». Celui-ci répond à « est-ce un
 * employé qui a le droit de toucher ça ? ». Sans lui, les trois rôles
 * partagéeient le même accès complet : un agent d'accueil pouvait modifier un
 * tarif journalier ou sortir un véhicule du parc.
 *
 * Usage : ->middleware('tier:gestionnaire') — « au moins gestionnaire ».
 */
class EnsureTier
{
    public function handle(Request $request, Closure $next, string $required): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login', ['locale' => app()->getLocale()]));
        }

        $niveauRequis = StaffTier::tryFromRole($required);

        // Un nom de rôle inconnu dans la configuration des routes est une
        // faute de frappe : on laisse passer plutôt que de bloquer toute
        // l'administration, et le journal garde la trace.
        if ($niveauRequis === null) {
            report(new \RuntimeException("Périmètre inconnu dans les routes : « {$required} »."));

            return $next($request);
        }

        $niveau = StaffTier::highest($user->getRoleNames());

        abort_if($niveau === null || ! $niveau->isAtLeast($niveauRequis), 403, __('auth.forbidden'));

        return $next($request);
    }
}
