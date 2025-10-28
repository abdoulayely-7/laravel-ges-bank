<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
