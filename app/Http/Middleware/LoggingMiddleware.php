<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
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

    /**
     * Log les opérations d'API
     */
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
            'duration' => $response ? (microtime(true) - LARAVEL_START) * 1000 : null, // en ms
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

    /**
     * Détermine le type d'opération (CRUD)
     */
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

    /**
     * Détermine la ressource concernée
     */
    private function determineResource(Request $request): string
    {
        $path = $request->path();

        // Extraire la ressource du chemin
        $segments = explode('/', $path);

        // Pour les routes API v1
        if (isset($segments[1]) && $segments[1] === 'v1' && isset($segments[2])) {
            $resource = $segments[2]; // ex: comptes, clients, etc.

            // Gérer les sous-ressources
            if (isset($segments[3]) && is_numeric($segments[3])) {
                return $resource . '/' . $segments[3]; // ex: comptes/123
            }

            return $resource;
        }

        return 'unknown';
    }
}
