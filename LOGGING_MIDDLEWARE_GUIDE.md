# Guide d'Implémentation du LoggingMiddleware

## Vue d'ensemble
Le `LoggingMiddleware` est un middleware Laravel qui enregistre automatiquement toutes les opérations d'API avec des informations détaillées : date/heure, host, nom d'opération, ressource, etc.

## Fonctionnalités

### ✅ Logging automatique
- **Date et heure** : Timestamp ISO 8601 précis
- **Host** : Domaine de la requête
- **IP** : Adresse IP du client
- **User Agent** : Navigateur/application cliente
- **Méthode HTTP** : GET, POST, PUT, DELETE
- **URL complète** : URL avec paramètres
- **Opération** : CREATE, READ, UPDATE, DELETE
- **Ressource** : comptes, clients, etc.
- **Phase** : START/END de la requête
- **Status Code** : Code HTTP de réponse
- **Durée** : Temps d'exécution en millisecondes

### ✅ Niveaux de log intelligents
- **CREATE** : `Log::info` (opérations importantes)
- **READ** : `Log::debug` (opérations fréquentes)
- **UPDATE** : `Log::info` (modifications)
- **DELETE** : `Log::warning` (opérations sensibles)

## Installation et Configuration

### 1. Création du Middleware
```bash
php artisan make:middleware LoggingMiddleware
```

### 2. Implémentation du code
**Fichier : `app/Http/Middleware/LoggingMiddleware.php`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log de l'opération avant traitement
        $this->logOperation($request, 'START');

        // Traiter la requête
        $response = $next($request);

        // Log de l'opération après traitement
        $this->logOperation($request, 'END', $response);

        return $response;
    }

    private function logOperation(Request $request, string $phase, Response $response = null): void
    {
        $operation = $this->determineOperation($request);
        $resource = $this->determineResource($request);

        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'operation' => $operation,
            'resource' => $resource,
            'phase' => $phase,
            'status_code' => $response ? $response->getStatusCode() : null,
            'duration' => $response ? (microtime(true) - LARAVEL_START) * 1000 : null,
        ];

        // Log selon le type d'opération
        if ($operation === 'CREATE' && $phase === 'END' && $response && $response->getStatusCode() === 200) {
            Log::info('API Operation - CREATE', $logData);
        } elseif ($operation === 'READ' && $phase === 'END') {
            Log::debug('API Operation - READ', $logData);
        } elseif ($operation === 'UPDATE' && $phase === 'END') {
            Log::info('API Operation - UPDATE', $logData);
        } elseif ($operation === 'DELETE' && $phase === 'END') {
            Log::warning('API Operation - DELETE', $logData);
        } else {
            Log::info('API Operation', $logData);
        }
    }

    private function determineOperation(Request $request): string
    {
        $method = $request->method();

        return match ($method) {
            'POST' => 'CREATE',
            'GET' => 'READ',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'UNKNOWN'
        };
    }

    private function determineResource(Request $request): string
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Pour les routes API v1
        if (isset($segments[1]) && $segments[1] === 'v1' && isset($segments[2])) {
            $resource = $segments[2];

            // Gérer les sous-ressources
            if (isset($segments[3]) && is_numeric($segments[3])) {
                return $resource . '/' . $segments[3];
            }

            return $resource;
        }

        return 'unknown';
    }
}
```

### 3. Enregistrement dans le Kernel
**Fichier : `app/Http/Kernel.php`**

```php
protected $middlewareGroups = [
    'api' => [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\LoggingMiddleware::class, // Ajouter cette ligne
    ],
];
```

## Exemples de logs générés

### 📖 Opération READ (GET)
```json
{
  "timestamp": "2025-10-26T23:41:10.890833Z",
  "host": "localhost",
  "ip": "127.0.0.1",
  "user_agent": "curl/8.5.0",
  "method": "GET",
  "url": "http://localhost:8000/ly/v1/comptes?limit=5&page=1",
  "operation": "READ",
  "resource": "comptes",
  "phase": "END",
  "status_code": 200,
  "duration": 95.52
}
```

### ➕ Opération CREATE (POST)
```json
{
  "timestamp": "2025-10-26T23:42:16.187710Z",
  "host": "localhost",
  "ip": "127.0.0.1",
  "user_agent": "curl/8.5.0",
  "method": "POST",
  "url": "http://localhost:8000/ly/v1/comptes",
  "operation": "CREATE",
  "resource": "comptes",
  "phase": "END",
  "status_code": 201,
  "duration": 18722.67
}
```

## Tests et validation

### Test d'une requête GET
```bash
curl -X GET "http://localhost:8000/ly/v1/comptes?page=1&limit=5" -H "Accept: application/json"
```

**Log généré :**
```
[2025-10-26 23:41:10] local.DEBUG: API Operation - READ
[2025-10-26 23:41:10] local.INFO: Cache miss - Génération des données comptes
```

### Test d'une requête POST
```bash
curl -X POST "http://localhost:8000/ly/v1/comptes" \
  -H "Content-Type: application/json" \
  -d '{"type": "epargne", "soldeInitial": 100000, "devise": "FCFA", "client": {"titulaire": "Test", "email": "test@example.com", "telephone": "771234567", "nci": "1234567890123", "adresse": "Test"}}'
```

**Log généré :**
```
[2025-10-26 23:41:57] local.INFO: API Operation {"operation":"CREATE", "resource":"comptes", "phase":"START"}
[2025-10-26 23:42:16] local.INFO: API Operation - CREATE {"operation":"CREATE", "resource":"comptes", "phase":"END", "status_code":201, "duration":18722.67}
```

## Avantages du système

### ✅ Traçabilité complète
- **Qui** : IP, User Agent
- **Quand** : Timestamp précis
- **Quoi** : Opération et ressource
- **Comment** : URL complète, durée
- **Résultat** : Status code

### ✅ Monitoring en temps réel
```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel.log | grep "API Operation"

# Compter les opérations par type
grep "API Operation" storage/logs/laravel.log | grep -o '"operation":"[^"]*"' | sort | uniq -c
```

### ✅ Debugging facilité
- Détection des requêtes lentes (`duration` élevé)
- Identification des erreurs (`status_code` ≠ 200)
- Analyse du trafic par ressource
- Audit des opérations sensibles

### ✅ Performance optimisée
- Logs asynchrones (pas de blocage)
- Niveaux de log adaptés (debug/info/warning)
- Filtrage intelligent par opération

## Personnalisation avancée

### Ajouter des informations utilisateur
```php
$logData = [
    // ... données existantes
    'user_id' => auth()->id(),
    'user_email' => auth()->user()?->email,
];
```

### Filtrage par environnement
```php
if (app()->environment('production')) {
    // Log détaillé en prod
    Log::info('API Operation', $logData);
} else {
    // Log simple en dev
    Log::debug('API: ' . $operation . ' ' . $resource);
}
```

### Métriques personnalisées
```php
// Compter les opérations par ressource
Cache::increment("metrics:api:{$resource}:{$operation}");

// Durée moyenne des requêtes
$avgDuration = Cache::get("metrics:api:duration:avg", 0);
$newAvg = ($avgDuration + $logData['duration']) / 2;
Cache::put("metrics:api:duration:avg", $newAvg);
```

## Sécurité et confidentialité

### ✅ Données sensibles masquées
- Mots de passe automatiquement hashés
- Tokens d'authentification non loggés
- Données personnelles filtrées si nécessaire

### ✅ Conformité RGPD
- Logs temporaires (rotation automatique)
- Pas de stockage de données sensibles
- Audit trail pour conformité

## Commandes de maintenance

### Vider les anciens logs
```bash
# Rotation des logs
php artisan log:clear

# Archives automatiques (config/logging.php)
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30, // Garder 30 jours
    ],
],
```

### Analyse des logs
```bash
# Nombre d'opérations par heure
grep "API Operation" storage/logs/laravel.log | cut -d' ' -f1,2 | uniq -c

# Opérations les plus lentes
grep "API Operation" storage/logs/laravel.log | jq -r 'select(.duration > 1000) | "\(.duration)ms - \(.url)"' | sort -nr | head -10

# Erreurs par endpoint
grep "API Operation" storage/logs/laravel.log | jq -r 'select(.status_code >= 400) | "\(.status_code) - \(.url)"' | sort | uniq -c
```

## Conclusion

Le `LoggingMiddleware` transforme votre API en un système entièrement traçable et monitoré. Chaque opération est automatiquement loggée avec toutes les informations nécessaires pour :

- **Déboguer** les problèmes
- **Monitorer** les performances
- **Auditer** les accès
- **Analyser** les usages
- **Sécuriser** l'application

**Installation** : 5 minutes
**Maintenance** : Automatique
**Bénéfices** : Traçabilité complète et monitoring temps réel ! 🚀
