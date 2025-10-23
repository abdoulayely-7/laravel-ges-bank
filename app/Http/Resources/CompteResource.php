<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CompteResource",
 *     title="Compte Resource",
 *     description="Représentation d'un compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="Identifiant unique du compte"),
 *     @OA\Property(property="numeroCompte", type="string", description="Numéro du compte"),
 *     @OA\Property(property="titulaire", type="string", description="Nom du titulaire du compte"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "courant", "cheque"}, description="Type de compte"),
 *     @OA\Property(property="solde", type="number", format="float", description="Solde du compte"),
 *     @OA\Property(property="devise", type="string", description="Devise du compte"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", description="Date de création du compte"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque"}, description="Statut du compte"),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", description="Dernière modification"),
 *         @OA\Property(property="version", type="integer", example=1, description="Version de l'API")
 *     )
 * )
 */
class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero_compte,
            'titulaire' => $this->client?->user?->name,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at?->toIso8601String(),
            'statut' => $this->statut,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toIso8601String(),
                'version' => 1,
            ],
        ];
    }
}
