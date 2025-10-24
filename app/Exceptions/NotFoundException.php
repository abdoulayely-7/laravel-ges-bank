<?php

namespace App\Exceptions;

class NotFoundException extends ApiException
{

    /**
     * Exception pour les ressources non trouvées
     */
    public function __construct(string $resource = "Ressource", string $identifier = null)
    {
        $message = $identifier
            ? "{$resource} avec l'identifiant '{$identifier}' non trouvé"
            : "{$resource} non trouvé";

        parent::__construct($message, 404);
    }
}
