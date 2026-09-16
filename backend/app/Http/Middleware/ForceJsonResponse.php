<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force la negociation de contenu en JSON sur toutes les routes d'API.
 *
 * Le back-end est une API sans etat (jeton Bearer) : il ne possede ni page de
 * connexion, ni page de verification d'adresse. Or plusieurs intergiciels de
 * Laravel redirigent vers ces pages lorsque la requete n'attend pas de JSON
 * (« auth » vers la route login, « verified » vers verification.notice). Ces
 * routes n'existant pas, la redirection levait une RouteNotFoundException
 * rendue en HTTP 500 — au lieu des HTTP 401 et 403 attendus — des qu'un appel
 * arrivait depuis un navigateur, par exemple une URL collee dans la barre
 * d'adresse par un chercheur consultant l'API.
 *
 * En imposant l'en-tete Accept, toute la chaine repond en JSON, quel que soit
 * le client. Le type de contenu des reponses reste decide par les controleurs
 * (les exports CSV conservent donc bien leur propre type).
 */
final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
