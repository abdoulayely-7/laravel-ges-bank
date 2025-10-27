<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification et gestion des tokens"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne les tokens d'accès",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", description="Token d'accès"),
     *                 @OA\Property(property="refresh_token", type="string", description="Token de rafraîchissement"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="role", type="string", enum={"client", "admin"})
     *             )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants invalides")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de validation invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $user = Auth::user();

        // Créer le token avec les scopes appropriés
        $tokenResult = $user->createToken('GES Bank API');
        $accessToken = $tokenResult->accessToken;

        // Créer un refresh token séparé
        $refreshToken = \Laravel\Passport\RefreshToken::create([
            'id' => \Illuminate\Support\Str::random(40),
            'access_token_id' => $tokenResult->token->id,
            'revoked' => false,
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'access_token' => $tokenResult->accessToken,
                'refresh_token' => $refreshToken->id, // On retourne l'ID du refresh token
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
                'user' => $user
            ]
        ])->cookie('refresh_token', $refreshToken->id, 60*24*7, null, null, true, true, false, 'Strict'); // 7 jours
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     description="Utilise le refresh token pour obtenir un nouveau token d'accès",
     *     operationId="refreshToken",
     *     tags={"Authentification"},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", description="Nouveau token d'accès"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Refresh token invalide")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $refreshTokenId = $request->cookie('refresh_token');

        if (!$refreshTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token manquant'
            ], 401);
        }

        // Trouver le refresh token
        $refreshToken = \Laravel\Passport\RefreshToken::find($refreshTokenId);

        if (!$refreshToken || $refreshToken->revoked || $refreshToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token invalide ou expiré'
            ], 401);
        }

        $user = $refreshToken->accessToken->user;

        // Révoquer l'ancien token
        $refreshToken->accessToken->delete();
        $refreshToken->delete();

        // Créer un nouveau token
        $newTokenResult = $user->createToken('GES Bank API', $this->getUserScopes($user));
        $newRefreshToken = $newTokenResult->token;
        $newRefreshToken->save();

        return response()->json([
            'success' => true,
            'message' => 'Token rafraîchi avec succès',
            'data' => [
                'access_token' => $newTokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]
        ])->cookie('refresh_token', $newRefreshToken->id, 60*24*7, null, null, true, true, false, 'Strict');
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès et le refresh token actuels",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();

        if ($accessToken) {
            app(TokenRepository::class)->revokeAccessToken($accessToken->id);
            app(RefreshTokenRepository::class)->revokeRefreshTokensByAccessTokenId($accessToken->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ])->cookie('refresh_token', '', -1);
    }

    /**
     * Détermine les scopes (permissions) de l'utilisateur selon son rôle
     */
    private function getUserScopes(User $user): array
    {
        $scopes = [];

        if ($user->client) {
            // Permissions pour les clients
            $scopes = [
                'view_own_comptes',
                'create_transaction',
                'view_own_transactions',
                'update_profile'
            ];
        }

        if ($user->admin) {
            // Permissions pour les administrateurs
            $scopes = [
                'manage_users',
                'manage_comptes',
                'view_all_transactions',
                'manage_admins',
                'view_reports',
                'manage_system'
            ];
        }

        return $scopes;
    }
}
