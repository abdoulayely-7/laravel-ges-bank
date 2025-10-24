<?php

namespace App\Exceptions;

use Exception;

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
}
