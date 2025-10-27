<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur est authentifié via Passport
     * et ajoute les informations utilisateur à la requête
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Token manquant ou invalide.',
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentification requise pour accéder à cette ressource'
                ]
            ], 401);
        }

        // Récupérer l'utilisateur authentifié
        $user = Auth::guard('api')->user();

        // Ajouter l'utilisateur à la requête pour un accès facile
        $request->merge(['authenticated_user' => $user]);

        // Ajouter les informations d'authentification aux headers pour debugging
        $request->headers->set('X-User-ID', $user->id);
        $request->headers->set('X-User-Role', $user->role);

        return $next($request);
    }
}
