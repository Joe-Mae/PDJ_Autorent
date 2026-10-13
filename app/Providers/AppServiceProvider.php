<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Les vues bilingues ont besoin de la langue courante partout, sans
        // que chaque contrôleur ait à la passer à chaque vue.
        View::composer('*', function ($view) {
            $view->with('locale', app()->getLocale());
        });

        // Paginator en Tailwind par défaut : on veut le rendu Bootstrap.
        Paginator::useBootstrapFive();

        // En production, toutes les URL doivent être en HTTPS, sinon les liens
        // de documents signés sortent en clair.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
