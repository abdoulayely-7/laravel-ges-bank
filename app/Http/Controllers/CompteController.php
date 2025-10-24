<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Requests\CompteIndexRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.2.0",
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

    /**
     * @OA\Get(
     *     path="/comptes/{numero}",
     *     summary="Récupérer un compte par numéro",
     *     description="Retourne les détails d'un compte spécifique basé sur son numéro",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte trouvé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
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
    public function show(string $numero)
    {
        try {
            $compte = Compte::query()->numero($numero)->with('client.user')->first();

            if (!$compte) {
                throw new NotFoundException('Compte', $numero);
            }

            return $this->success(
                new CompteResource($compte),
                'Compte trouvé avec succès'
            );
        } catch (NotFoundException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/client/{telephone}",
     *     summary="Récupérer les comptes d'un client par téléphone",
     *     description="Retourne la liste des comptes d'un client basé sur son numéro de téléphone",
     *     operationId="getComptesByTelephone",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         description="Numéro de téléphone du client",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comptes trouvés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes trouvés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteResource"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte trouvé pour ce numéro de téléphone")
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
    public function getComptesByTelephone(string $telephone)
    {
        try {
            $comptes = Compte::query()->client($telephone)->with('client.user')->get();

            if ($comptes->isEmpty()) {
                throw new NotFoundException('Comptes', "téléphone {$telephone}");
            }

            return $this->success(
                CompteResource::collection($comptes),
                'Comptes trouvés avec succès'
            );
        } catch (NotFoundException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}

/**
 *
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


