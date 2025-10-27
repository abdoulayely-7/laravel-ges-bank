<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CompteDeleteResource",
 *     title="Suppression de Compte",
 *     description="Représentation d'un compte après suppression (soft delete)",
 *     @OA\Property(property="id", type="string", format="uuid", description="Identifiant unique du compte"),
 *     @OA\Property(property="numeroCompte", type="string", description="Numéro du compte"),
 *     @OA\Property(property="statut", type="string", enum={"ferme"}, description="Statut du compte après suppression"),
 *     @OA\Property(property="dateFermeture", type="string", format="date-time", description="Date de fermeture du compte")
 * )
 */
class CompteDeleteResource extends JsonResource
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
            'statut' => $this->statut,
            'dateFermeture' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
