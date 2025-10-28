<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="DeblocageResource",
 *     title="Deblocage Resource",
 *     description="Représentation d'un compte après déblocage",
 *     @OA\Property(property="id", type="string", format="uuid", description="Identifiant unique du compte"),
 *     @OA\Property(property="statut", type="string", enum={"actif"}, description="Statut du compte"),
 *     @OA\Property(property="dateDeblocage", type="string", format="date-time", description="Date de déblocage")
 * )
 */
class DeblocageResource extends JsonResource
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
            'dateDeblocage' => $this->date_deblocage?->toIso8601String(),
        ];
    }
}
