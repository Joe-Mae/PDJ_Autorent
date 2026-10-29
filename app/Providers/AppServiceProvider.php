<?php

namespace App\Providers;

use App\Services\MailConfigurator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        // Les réglages SMTP saisis depuis /admin/emails vivent en base : ils
        // doivent écraser .env avant qu'un mailer ne soit construit. Sur une
        // base pas encore migrée (installation, test unitaire), on garde la
        // configuration du fichier .env.
        try {
            $this->app->make(MailConfigurator::class)->apply();
        } catch (Throwable) {
            // Base non migrée : la configuration du fichier .env reste valable.
        }

        // En production, toutes les URL doivent être en HTTPS, sinon les liens
        // de documents signés sortent en clair.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
