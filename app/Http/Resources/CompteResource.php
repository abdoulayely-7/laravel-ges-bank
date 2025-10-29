<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, description="Motif du blocage si le compte est bloqué"),
 *     @OA\Property(property="dateDebutBlocage", type="string", format="date-time", nullable=true, description="Date de début du blocage planifié"),
 *     @OA\Property(property="dureeBlocage", type="object", nullable=true, description="Informations sur la durée du blocage",
 *         @OA\Property(property="valeur", type="integer", description="Valeur de la durée"),
 *         @OA\Property(property="unite", type="string", enum={"minutes", "heures", "jours", "mois"}, description="Unité de la durée")
 *     ),
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
            'motifBlocage' => $this->motif_blocage,
            'dateDebutBlocage' => $this->date_debut_blocage_planifiee ? Carbon::parse($this->date_debut_blocage_planifiee)->toIso8601String() : null,
            'dureeBlocage' => $this->duree_blocage_valeur ? [
                'valeur' => $this->duree_blocage_valeur,
                'unite' => $this->duree_blocage_unite,
            ] : null,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toIso8601String(),
                'version' => 1,
            ],
        ];
    }
}
