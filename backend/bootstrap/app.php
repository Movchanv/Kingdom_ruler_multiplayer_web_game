<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserCanResearch;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )

    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'researcher' => EnsureUserCanResearch::class,
        ]);

        // Toute requete d'API est traitee comme une requete JSON, quel que soit
        // le client (navigateur compris) : voir ForceJsonResponse.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        /*
         * Laravel installe par defaut une redirection des visiteurs non
         * authentifies vers la route « login ». Ce back-end est une API sans etat
         * (jeton Bearer) : cette route n'existe pas, et l'appeler leve une
         * RouteNotFoundException rendue en HTTP 500. On renvoie donc null, ce qui
         * produit l'AuthenticationException attendue, soit un HTTP 401.
         */
        $middleware->redirectGuestsTo(fn (Request $request): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * L'API est sans etat (jeton Bearer) et ne comporte aucune route « login ».
         * Sans cette regle, un appel non authentifie emis par un navigateur
         * (en-tete Accept: text/html, par exemple une URL collee dans la barre
         * d'adresse) fait tenter a Laravel une redirection vers la route « login » :
         * elle n'existe pas, d'ou une RouteNotFoundException rendue en HTTP 500
         * au lieu du HTTP 401 attendu. On force donc le rendu JSON sur /api/*.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
