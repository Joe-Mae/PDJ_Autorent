<?php

use App\Exceptions\BookingException;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\EnsureTier;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group([
                base_path('routes/admin.php'),
                base_path('routes/agent.php'),
            ]);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'staff' => EnsureStaff::class,
            'tier' => EnsureTier::class,
        ]);

        // La route de connexion est sous le préfixe /fr ou /en, alors que les
        // écrans d'administration sont hors de ce préfixe. Sans cette langue,
        // la redirection d'un visiteur non connecté levait une
        // UrlGenerationException — un 500 au lieu du formulaire de connexion.
        $middleware->redirectGuestsTo(fn (Request $request): string => route('login', [
            'locale' => in_array(session(SetLocale::SESSION_KEY), SetLocale::SUPPORTED, true)
                ? session(SetLocale::SESSION_KEY)
                : app()->getLocale(),
        ]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Une règle métier violée n'est pas une erreur 500 : on renvoie le
        // client vers la page qui peut le débloquer, pas vers une page d'erreur.
        $exceptions->render(function (BookingException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });

        // Un envoi trop lourd pour post_max_size est entièrement ignoré par
        // PHP : plus aucun champ n'arrive, et l'agent voit un formulaire vide
        // sans explication.
        //
        // ValidatePostSize fait partie du middleware global, qui passe AVANT
        // StartSession : au moment où l'exception est levée, la session n'est
        // pas encore ouverte. Un `back()->withErrors()` y serait écrit puis
        // perdu — l'agent retomberait sur le formulaire, sans un mot. On rend
        // donc la page directement.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('admin.upload_too_large')], 413);
            }

            return response()->view('errors.upload', [
                'retour' => $request->headers->get('referer'),
            ], 413, ['Retry-After' => '60']);
        });
    })->create();
