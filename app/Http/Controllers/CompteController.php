<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\CompteIndexRequest;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    private CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Récupérer la liste des comptes",
     *     description="Retourne une liste paginée des comptes avec possibilité de filtrage et tri",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "courant", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Statut du compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par nom du titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des comptes récupérée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="items", type="array",
     *                     @OA\Items(ref="#/components/schemas/CompteResource")
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="currentPage", type="integer", example=1),
     *                     @OA\Property(property="totalPages", type="integer", example=5),
     *                     @OA\Property(property="totalItems", type="integer", example=50),
     *                     @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                     @OA\Property(property="hasNext", type="boolean", example=true),
     *                     @OA\Property(property="hasPrevious", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="links", type="object",
     *                     @OA\Property(property="self", type="string", example="http://localhost:8000/api/v1/comptes?page=1"),
     *                     @OA\Property(property="next", type="string", example="http://localhost:8000/api/v1/comptes?page=2"),
     *                     @OA\Property(property="first", type="string", example="http://localhost:8000/api/v1/comptes?page=1"),
     *                     @OA\Property(property="last", type="string", example="http://localhost:8000/api/v1/comptes?page=5")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètres invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur")
     *         )
     *     )
     * )
     */
    public function index(CompteIndexRequest $request)
    {
        try {
            $data = $this->compteService->rechercherEtPaginer($request->validated());
            return $this->success(
                $data,
                'Liste des comptes récupérée avec succès'
            );
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}

/**
 * @OA\Schema(
 *     schema="Pagination",
 *     title="Pagination",
 *     description="Informations de pagination",
 *     @OA\Property(property="currentPage", type="integer", description="Page actuelle"),
 *     @OA\Property(property="totalPages", type="integer", description="Nombre total de pages"),
 *     @OA\Property(property="totalItems", type="integer", description="Nombre total d'éléments"),
 *     @OA\Property(property="itemsPerPage", type="integer", description="Nombre d'éléments par page"),
 *     @OA\Property(property="hasNext", type="boolean", description="Si il y a une page suivante"),
 *     @OA\Property(property="hasPrevious", type="boolean", description="Si il y a une page précédente")
 * )
 *
 * @OA\Schema(
 *     schema="Links",
 *     title="Links",
 *     description="Liens de navigation",
 *     @OA\Property(property="self", type="string", description="Lien vers la page actuelle"),
 *     @OA\Property(property="next", type="string", nullable=true, description="Lien vers la page suivante"),
 *     @OA\Property(property="first", type="string", description="Lien vers la première page"),
 *     @OA\Property(property="last", type="string", description="Lien vers la dernière page")
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     title="Réponse API",
 *     description="Structure de réponse standard de l'API",
 *     @OA\Property(property="success", type="boolean", description="Statut de succès"),
 *     @OA\Property(property="message", type="string", description="Message de réponse"),
 *     @OA\Property(property="data", type="object", description="Données de réponse")
 * )
 */
