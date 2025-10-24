<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RatingMiddleware
{
    /**
     * Nombre maximum de requêtes par jour
     */
    private const MAX_REQUESTS_PER_DAY = 5;

    /**
     * Durée de blocage en heures
     */
    private const BLOCK_DURATION_HOURS = 24;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $ipAddress = $request->ip();
        $endpoint = $request->path();
        $method = $request->method();
        $userAgent = $request->userAgent();

        // Clé de cache pour cette IP (par jour)
        $cacheKey = "rating_limit:{$ipAddress}:" . Carbon::now()->format('Y-m-d');

        // Récupérer le compteur actuel
        $requestCount = Cache::get($cacheKey, 0);

        // Vérifier si l'utilisateur a dépassé la limite
        if ($requestCount >= self::MAX_REQUESTS_PER_DAY) {
            // Logger l'événement de dépassement
            $this->logRatingLimitExceeded($ipAddress, $endpoint, $method, $userAgent, $requestCount);

            // Retourner une réponse d'erreur
            return $this->ratingLimitExceededResponse();
        }

        // Incrémenter le compteur
        Cache::put($cacheKey, $requestCount + 1, Carbon::now()->endOfDay());

        // Ajouter les headers de rating limit
        $response = $next($request);

        if (method_exists($response, 'headers')) {
            $response->headers->set('X-RatingLimit-Limit', self::MAX_REQUESTS_PER_DAY);
            $response->headers->set('X-RatingLimit-Remaining', max(0, self::MAX_REQUESTS_PER_DAY - $requestCount - 1));
            $response->headers->set('X-RatingLimit-Reset', Carbon::now()->endOfDay()->timestamp);
        }

        return $response;
    }

    /**
     * Logger les dépassements de limite de rating
     */
    private function logRatingLimitExceeded(string $ipAddress, string $endpoint, string $method, ?string $userAgent, int $requestCount): void
    {
        Log::warning('Rating limit exceeded - Daily limit reached', [
            'ip_address' => $ipAddress,
            'endpoint' => $endpoint,
            'method' => $method,
            'user_agent' => $userAgent,
            'request_count' => $requestCount,
            'max_allowed' => self::MAX_REQUESTS_PER_DAY,
            'timestamp' => Carbon::now()->toISOString(),
            'block_duration_hours' => self::BLOCK_DURATION_HOURS,
        ]);
    }

    /**
     * Retourner une réponse d'erreur de rating limit
     */
    private function ratingLimitExceededResponse()
    {
        $resetTime = Carbon::now()->endOfDay();
        $remainingSeconds = Carbon::now()->diffInSeconds($resetTime);

        return response()->json([
            'success' => false,
            'message' => 'Limite de requêtes journalière dépassée. Réessayez demain.',
            'error' => 'RATING_LIMIT_EXCEEDED',
            'retry_after' => $remainingSeconds,
            'max_requests_per_day' => self::MAX_REQUESTS_PER_DAY,
        ], 429, [
            'Retry-After' => $remainingSeconds,
            'X-RatingLimit-Retry-After' => $remainingSeconds,
        ]);
    }
}
