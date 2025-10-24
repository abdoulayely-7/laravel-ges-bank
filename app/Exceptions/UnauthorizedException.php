<?php

namespace App\Exceptions;

/**
 * Exception pour les accès non autorisés
 */
class UnauthorizedException extends ApiException
{
    public function __construct(string $message = "Accès non autorisé")
    {
        parent::__construct($message, 401);
    }
}
