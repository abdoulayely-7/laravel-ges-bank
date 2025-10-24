<?php

namespace App\Exceptions;

/**
 * Exception pour les comptes bloqués
 */
class CompteBloqueException extends ApiException
{
    public function __construct(string $numeroCompte, string $motifBlocage = null)
    {
        $message = "Le compte {$numeroCompte} est bloqué";
        if ($motifBlocage) {
            $message .= " : {$motifBlocage}";
        }

        parent::__construct($message, 423); // 423 Locked
    }
}
