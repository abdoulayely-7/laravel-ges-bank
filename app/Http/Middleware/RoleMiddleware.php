<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur a les permissions (scopes) nécessaires
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$requiredScopes  Les scopes requis pour accéder à la ressource
     */
    public function handle(Request $request, Closure $next, ...$requiredScopes): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource'
                ]
            ], 401);
        }

        // Récupérer le token actuel via la requête
        $token = $request->user()->token ?? null;

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Votre token d\'accès est invalide'
                ]
            ], 401);
        }

        // Vérifier les scopes
        if ($token && $token->count() > 0) {
            $userScopes = $token->pluck('name')->toArray();
        } else {
            $userScopes = [];
        }

        // Vérifier si l'utilisateur a au moins un des scopes requis
        $hasRequiredScope = false;
        foreach ($requiredScopes as $requiredScope) {
            if (in_array($requiredScope, $userScopes) ||
                in_array('*', $userScopes) ||
                in_array('GES Bank API', $userScopes)) {
                $hasRequiredScope = true;
                break;
            }
        }

        if (!$hasRequiredScope) {
            return response()->json([
                'success' => false,
                'message' => 'Permissions insuffisantes',
                'error' => [
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource',
                    'details' => [
                        'required_scopes' => $requiredScopes,
                        'user_scopes' => $userScopes,
                        'user_role' => $user->role
                    ]
                ]
            ], 403);
        }

        // Ajouter les informations d'autorisation à la requête
        $request->merge([
            'user_scopes' => $userScopes,
            'user_role' => $user->role,
            'authorized_scopes' => $requiredScopes
        ]);

        return $next($request);
    }
}
