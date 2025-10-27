# Guide Complet d'Authentification Laravel Passport

## Introduction
Ce guide explique en détail comment fonctionne l'authentification OAuth2 avec Laravel Passport dans votre projet. Même un débutant pourra comprendre chaque concept étape par étape.

## Qu'est-ce que OAuth2 ?
OAuth2 est un protocole d'autorisation qui permet à une application d'accéder aux ressources d'un utilisateur sans connaître son mot de passe. Au lieu de partager le mot de passe, l'application reçoit un "jeton d'accès" (access token).

## Qu'est-ce que Laravel Passport ?
Laravel Passport est une implémentation OAuth2 pour Laravel qui facilite la création d'API sécurisées. Il transforme votre application Laravel en serveur OAuth2.

## Concepts de Base

### 1. Clients OAuth2
Un "client" est une application qui veut accéder aux ressources de l'utilisateur.

**Types de clients :**
- **Client confidentiel** : Peut garder un secret (applications serveur)
- **Client public** : Ne peut pas garder un secret (applications mobiles, SPAs)

### 2. Tokens d'Accès (Access Tokens)
Un token d'accès est une chaîne de caractères qui prouve que l'utilisateur a autorisé l'application à accéder à ses ressources.

**Exemple de token :**
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### 3. Scopes (Portées)
Les scopes définissent quelles permissions l'application a. Par exemple :
- `read` : peut lire les données
- `write` : peut modifier les données
- `view_own_comptes` : peut voir ses propres comptes

## Installation et Configuration

### 1. Installation du Package
```bash
composer require laravel/passport
```

### 2. Migration des Tables
Passport crée plusieurs tables pour stocker les clients, tokens, etc.
```bash
php artisan migrate
```

### 3. Installation de Passport
```bash
php artisan passport:install
```

Cette commande :
- Crée des clés de chiffrement RSA
- Crée des clients OAuth2 par défaut
- Génère des clés personnelles

### 4. Configuration du Modèle User
Dans `app/Models/User.php`, ajoutez le trait `HasApiTokens` :

```php
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    // Vos autres méthodes...
}
```

### 5. Configuration des Routes API
Dans `routes/api.php`, ajoutez les routes Passport :

```php
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;

// Routes pour l'authentification OAuth2
Route::post('/oauth/token', [AccessTokenController::class, 'issueToken']);
Route::get('/oauth/clients', [AuthorizedAccessTokenController::class, 'forUser']);
```

## Flux d'Authentification

### 1. Enregistrement d'un Client
Un client doit être enregistré dans la base de données :

```php
// Via tinker ou seeder
$client = new Laravel\Passport\Client();
$client->name = 'GES Bank API';
$client->secret = 'secret-key';
$client->redirect = 'http://localhost';
$client->personal_access_client = false;
$client->password_client = true;
$client->save();
```

### 2. Demande de Token (Password Grant)
L'application demande un token en envoyant :
- `grant_type`: "password"
- `client_id`: ID du client
- `client_secret`: Secret du client
- `username`: Email de l'utilisateur
- `password`: Mot de passe de l'utilisateur
- `scope`: Permissions demandées

**Exemple de requête :**
```bash
curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=password" \
  -d "client_id=7" \
  -d "client_secret=your-secret" \
  -d "username=user@example.com" \
  -d "password=password" \
  -d "scope=*"
```

**Réponse :**
```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "refresh-token-here"
}
```

### 3. Utilisation du Token
Pour accéder aux ressources protégées, ajoutez le token dans l'en-tête Authorization :

```bash
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
     http://localhost:8000/api/v1/comptes
```

## Middleware de Protection

### 1. Middleware Auth
Dans `app/Http/Kernel.php`, ajoutez :

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### 2. Protection des Routes
```php
// Route protégée nécessitant authentification
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route avec scopes spécifiques
Route::middleware('auth:api', 'scope:view_own_comptes')->get('/comptes', [CompteController::class, 'index']);
```

### 3. Middleware Personnalisé pour les Rôles
Dans votre `RoleMiddleware.php` :

```php
public function handle(Request $request, Closure $next, ...$requiredScopes): Response
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json(['error' => 'Non authentifié'], 401);
    }

    // Récupérer le token actuel
    $token = $request->user()->token ?? null;

    if (!$token) {
        return response()->json(['error' => 'Token invalide'], 401);
    }

    // Vérifier les scopes
    $userScopes = $token->scopes ?? [];

    // Vérifier si l'utilisateur a les permissions
    foreach ($requiredScopes as $scope) {
        if (!in_array($scope, $userScopes)) {
            return response()->json(['error' => 'Permissions insuffisantes'], 403);
        }
    }

    return $next($request);
}
```

## Structure des Tables

### 1. oauth_clients
Stocke les informations des clients OAuth2 :
- `id`: Identifiant unique
- `name`: Nom du client
- `secret`: Secret du client
- `redirect`: URL de redirection
- `personal_access_client`: Si c'est un client d'accès personnel
- `password_client`: Si c'est un client password grant

### 2. oauth_access_tokens
Stocke les tokens d'accès :
- `id`: Identifiant du token
- `user_id`: ID de l'utilisateur
- `client_id`: ID du client
- `scopes`: Permissions du token (JSON)
- `revoked`: Si le token est révoqué
- `expires_at`: Date d'expiration

### 3. oauth_refresh_tokens
Stocke les tokens de rafraîchissement pour renouveler les access tokens.

## Scopes et Permissions

### Définition des Scopes
Dans un service provider ou middleware :

```php
// Exemple de scopes disponibles
$scopes = [
    'view_own_comptes' => 'Voir ses propres comptes',
    'create_comptes' => 'Créer de nouveaux comptes',
    'edit_comptes' => 'Modifier les comptes',
    'delete_comptes' => 'Supprimer des comptes',
    '*' => 'Accès complet'
];
```

### Vérification des Permissions
```php
// Dans un contrôleur
public function index(Request $request)
{
    $user = $request->user();

    // Vérifier si l'utilisateur peut voir tous les comptes
    if ($user->tokenCan('view_all_comptes')) {
        return Compte::all();
    }

    // Sinon, seulement ses propres comptes
    return $user->comptes;
}
```

## Gestion des Tokens

### 1. Création de Tokens Personnels
```php
$user = User::find(1);

// Créer un token personnel
$token = $user->createToken('Token Name', ['scope1', 'scope2']);

// Le token est retourné avec sa valeur
$accessToken = $token->accessToken;
```

### 2. Révocation de Tokens
```php
// Révoquer un token spécifique
$token->revoke();

// Révoquer tous les tokens d'un utilisateur
$user->tokens()->delete();
```

### 3. Rafraîchissement de Tokens
Utilisez le `refresh_token` pour obtenir un nouveau `access_token` :

```bash
curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=refresh_token" \
  -d "refresh_token=your-refresh-token" \
  -d "client_id=your-client-id" \
  -d "client_secret=your-client-secret"
```

## Sécurité

### 1. Chiffrement des Tokens
Passport utilise JWT (JSON Web Tokens) signés avec des clés RSA privées.

### 2. Expiration des Tokens
Par défaut, les tokens expirent après 1 an, mais vous pouvez configurer cela.

### 3. HTTPS Obligatoire
En production, utilisez toujours HTTPS pour sécuriser les tokens.

### 4. Validation des Scopes
Vérifiez toujours les scopes avant d'autoriser l'accès aux ressources.

## Débogage et Tests

### 1. Vérifier un Token
```php
// Dans un contrôleur ou middleware
$user = Auth::guard('api')->user();
$token = $user->token;

dd([
    'user' => $user->name,
    'scopes' => $token->scopes,
    'client' => $token->client->name,
    'expires_at' => $token->expires_at
]);
```

### 2. Tester avec cURL
```bash
# 1. Obtenir un token
TOKEN=$(curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=password&client_id=7&client_secret=secret&username=user@example.com&password=password&scope=*" \
  -s | jq -r '.access_token')

# 2. Utiliser le token
curl -H "Authorization: Bearer $TOKEN" \
     http://localhost:8000/api/v1/comptes
```

## Erreurs Courantes

### 1. "Unauthenticated"
- Le token est manquant ou invalide
- Le token a expiré
- L'utilisateur n'existe plus

### 2. "Insufficient Scope"
- Le token n'a pas les permissions nécessaires
- Les scopes ne correspondent pas

### 3. "Invalid Client"
- Le client_id n'existe pas
- Le client_secret est incorrect

## Configuration Avancée

### 1. Expiration des Tokens
Dans `config/auth.php` :
```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

### 2. Routes Passport Personnalisées
Vous pouvez personnaliser les routes dans un service provider.

### 3. Middleware Scopes
```php
// Vérifier plusieurs scopes
Route::middleware('scopes:view_own_comptes,create_comptes')->get('/comptes', ...);

// Vérifier au moins un scope
Route::middleware('scope:view_own_comptes')->get('/comptes', ...);
```

## Résumé
Laravel Passport transforme votre application en serveur OAuth2 complet. Voici le flux typique :

1. **Enregistrement** : L'application s'enregistre comme client OAuth2
2. **Authentification** : L'utilisateur se connecte et autorise l'application
3. **Token** : L'application reçoit un access token
4. **Accès** : L'application utilise le token pour accéder aux API
5. **Vérification** : Le serveur vérifie le token et les permissions à chaque requête

Cette approche sécurisée permet de créer des API robustes sans partager les mots de passe des utilisateurs.
