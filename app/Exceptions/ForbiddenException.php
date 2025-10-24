<?php

namespace App\Exceptions;

/**
 * Exception pour les accès interdits
 */
class ForbiddenException extends ApiException
{
    public function __construct(string $message = "Accès interdit")
    {
        parent::__construct($message, 403);
    }
}
