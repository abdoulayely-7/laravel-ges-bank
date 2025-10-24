<?php

namespace App\Exceptions;



/**
 * Exception pour les soldes insuffisants
 */
class SoldeInsuffisantException extends ApiException
{
    public function __construct(float $soldeActuel, float $montantDemande)
    {
        $message = "Solde insuffisant. Solde actuel : {$soldeActuel}, Montant demandé : {$montantDemande}";

        parent::__construct($message, 402); // 402 Payment Required
    }
}
