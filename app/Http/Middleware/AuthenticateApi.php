<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupérer le token de l'environnement dans la méthode
        $API_TOKEN = env('BEARER_TOKEN');

        // Obtenir le token Bearer de la requête
        $token = $request->bearerToken();

        // Comparer le token de la requête avec celui de l'environnement
        if ($token === $API_TOKEN) {
            return $next($request);
        }

        // Retourner une réponse non autorisée si le token est incorrect
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
