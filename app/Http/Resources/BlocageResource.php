<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BlocageResource",
 *     title="Blocage Resource",
 *     description="Représentation d'un compte après blocage",
 *     @OA\Property(property="id", type="string", format="uuid", description="Identifiant unique du compte"),
 *     @OA\Property(property="statut", type="string", enum={"bloque"}, description="Statut du compte"),
 *     @OA\Property(property="motifBlocage", type="string", description="Motif du blocage"),
 *     @OA\Property(property="dateBlocage", type="string", format="date-time", description="Date de début du blocage"),
 *     @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", description="Date prévue de déblocage")
 * )
 */
class BlocageResource extends JsonResource
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
            'statut' => $this->statut,
            'motifBlocage' => $this->motif_blocage,
            'dateBlocage' => $this->date_blocage?->toIso8601String(),
            'dateDeblocagePrevue' => $this->date_deblocage_prevue?->toIso8601String(),
        ];
    }
}
