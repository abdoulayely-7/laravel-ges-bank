<?php

namespace App\Http\Middleware;

use App\Models\ApiRateLimit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RateLimitMiddleware
{
    /**
     * Nombre maximum de requêtes par minute
     */
    private const MAX_REQUESTS_PER_MINUTE = 60;

    /**
     * Durée de blocage en minutes
     */
    private const BLOCK_DURATION_MINUTES = 2;

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

        // Vérifier si l'utilisateur est déjà bloqué
        $existingLimit = ApiRateLimit::where('ip_address', $ipAddress)
            ->where('blocked', true)
            ->where('blocked_until', '>', Carbon::now())
            ->first();

        if ($existingLimit) {
            return $this->rateLimitExceededResponse($existingLimit->blocked_until);
        }

        // Définir la fenêtre de temps (dernière minute)
        $windowStart = Carbon::now()->startOfMinute();
        $windowEnd = Carbon::now()->endOfMinute();

        // Récupérer ou créer l'enregistrement de rate limit
        $rateLimit = ApiRateLimit::firstOrNew([
            'ip_address' => $ipAddress,
            'endpoint' => $endpoint,
            'method' => $method,
            'window_start' => $windowStart,
        ]);

        // Si c'est un nouvel enregistrement ou une nouvelle fenêtre
        if (!$rateLimit->exists || $rateLimit->window_start->ne($windowStart)) {
            $rateLimit->fill([
                'user_agent' => $userAgent,
                'request_count' => 1,
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
                'blocked' => false,
                'blocked_until' => null,
                'metadata' => [
                    'headers' => $request->headers->all(),
                    'first_request_at' => Carbon::now()->toISOString(),
                ],
            ]);
            $rateLimit->save();
        } else {
            // Incrémenter le compteur
            $rateLimit->incrementRequestCount();

            // Mettre à jour les métadonnées
            $metadata = $rateLimit->metadata ?? [];
            $metadata['last_request_at'] = Carbon::now()->toISOString();
            $rateLimit->metadata = $metadata;
            $rateLimit->save();
        }

        // Vérifier si la limite est dépassée
        if ($rateLimit->request_count > self::MAX_REQUESTS_PER_MINUTE) {
            // Bloquer l'utilisateur
            $rateLimit->blockForMinutes(self::BLOCK_DURATION_MINUTES);

            // Log l'événement de blocage
            Log::warning('Rate limit exceeded', [
                'ip' => $ipAddress,
                'endpoint' => $endpoint,
                'method' => $method,
                'request_count' => $rateLimit->request_count,
                'blocked_until' => $rateLimit->blocked_until,
            ]);

            return $this->rateLimitExceededResponse($rateLimit->blocked_until);
        }

        // Ajouter les headers de rate limit à la réponse
        $response = $next($request);

        if (method_exists($response, 'headers')) {
            $response->headers->set('X-RateLimit-Limit', self::MAX_REQUESTS_PER_MINUTE);
            $response->headers->set('X-RateLimit-Remaining', max(0, self::MAX_REQUESTS_PER_MINUTE - $rateLimit->request_count));
            $response->headers->set('X-RateLimit-Reset', $windowEnd->timestamp);
        }

        return $response;
    }

    /**
     * Retourner une réponse d'erreur de rate limit
     */
    private function rateLimitExceededResponse(Carbon $blockedUntil)
    {
        $remainingSeconds = Carbon::now()->diffInSeconds($blockedUntil);

        return response()->json([
            'success' => false,
            'message' => 'Trop de requêtes. Réessayez dans ' . $remainingSeconds . ' secondes.',
            'error' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $remainingSeconds,
        ], 429, [
            'Retry-After' => $remainingSeconds,
            'X-RateLimit-Retry-After' => $remainingSeconds,
        ]);
    }
}
