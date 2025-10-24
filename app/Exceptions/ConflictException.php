<?php

namespace App\Exceptions;

/**
 * Exception pour les conflits (ressource existe déjà)
 */
class ConflictException extends ApiException
{
    public function __construct(string $message = "Conflit de données")
    {
        parent::__construct($message, 409);
    }
}
