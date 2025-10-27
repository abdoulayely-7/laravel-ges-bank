<?php

namespace App\Exceptions;

use Exception;

class CompteNotFoundException extends Exception
{
    protected $compteId;

    public function __construct(string $compteId)
    {
        $this->compteId = $compteId;
        parent::__construct("Le compte avec l'ID {$compteId} n'existe pas", 404);
    }

    public function getCompteId(): string
    {
        return $this->compteId;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => $this->getMessage(),
                'details' => [
                    'compteId' => $this->compteId
                ]
            ]
        ], 404);
    }
}
