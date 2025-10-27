<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Exception de base pour l'API
 */
class ApiException extends Exception
{
    protected int $statusCode;
    protected array $errors;

    public function __construct(string $message = "Erreur API", int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retourne une réponse JSON cohérente pour toutes les exceptions API
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'details' => $this->errors
            ]
        ], $this->statusCode);
    }
}
