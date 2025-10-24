<?php

namespace App\Exceptions;


/**
 * Exception pour les erreurs de validation
 */
class ValidationException extends ApiException
{
    public function __construct(array $errors = [], string $message = "Données invalides")
    {
        parent::__construct($message, 422, $errors);
    }
}
