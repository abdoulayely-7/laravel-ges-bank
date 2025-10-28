<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\CompteNotFoundException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Requests\BlocageCompteRequest;
use App\Http\Requests\CompteIndexRequest;
use App\Http\Requests\DeblocageCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\BlocageResource;
use App\Http\Resources\CompteDeleteResource;
use App\Http\Resources\CompteDetailResource;
use App\Http\Resources\CompteResource;
use App\Http\Resources\DeblocageResource;
use App\Models\Client;
use App\Models\Compte;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.4.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="https://abdoulaye-lylaravel-ges-bank.onrender.com/ly/v1",
 *     description="Serveur de production"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/ly/v1",
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
     *     @OA\Parameter(
     *         name="actifs_epargne_cheque",
     *         in="query",
     *         description="Filtrer uniquement les comptes actifs de type épargne ou chèque",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false"}, default="false")
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
     *                     @OA\Property(property="self", type="string", example="https://abdoulaye-lylaravel-ges-bank.onrender.com/ly/v1/comptes?page=1"),
     *                     @OA\Property(property="next", type="string", example="https://abdoulaye-lylaravel-ges-bank.onrender.com/ly/v1/comptes?page=2"),
     *                     @OA\Property(property="first", type="string", example="https://abdoulaye-lylaravel-ges-bank.onrender.com/ly/v1/comptes?page=1"),
     *                     @OA\Property(property="last", type="string", example="https://abdoulaye-lylaravel-ges-bank.onrender.com/ly/v1/comptes?page=5")
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
     *     path="/comptes/id/{compte}",
     *     summary="Récupérer un compte par ID",
     *     description="Retourne les détails d'un compte spécifique basé sur son ID UUID",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="ID UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte trouvé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteDetailResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
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
    public function getCompteById(Compte $compte)
    {
        try {
            // $compte = Compte::find($compteId);

            // if (!$compte) {
            //     throw new NotFoundException('Compte', $compteId);
            // }

            return $this->success(
                new CompteDetailResource($compte->load('client.user')),
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

    /**
     * @OA\Patch(
     *     path="/comptes/{compte}",
     *     summary="Mettre à jour les informations du client",
     *     description="Met à jour les informations du client associé à un compte bancaire",
     *     operationId="updateClient",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="ID UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *             @OA\Property(property="informationsClient", type="object",
     *                 @OA\Property(property="telephone", type="string", example="+221771234568"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *                 @OA\Property(property="password", type="string", example="nouveauMotDePasse123"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Au moins un champ de modification doit être fourni"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le Compte avec l'ID spécifié n'existe pas")
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
    public function updateClient(UpdateClientRequest $request, Compte $compte)
    {
        try {
            $validatedData = $request->validated();

            // Préparer les données de mise à jour
            $userData = [];
            $clientData = [];

            // Traiter le titulaire (nom de l'utilisateur)
            if (isset($validatedData['titulaire'])) {
                $userData['name'] = $validatedData['titulaire'];
            }

            // Traiter les informations client
            if (isset($validatedData['informationsClient'])) {
                $clientInfo = $validatedData['informationsClient'];

                if (isset($clientInfo['telephone'])) {
                    $clientData['telephone'] = $clientInfo['telephone'];
                }

                if (isset($clientInfo['nci'])) {
                    $clientData['nci'] = $clientInfo['nci'];
                }

                if (isset($clientInfo['email'])) {
                    $userData['email'] = $clientInfo['email'];
                }

                if (isset($clientInfo['password'])) {
                    $userData['password'] = bcrypt($clientInfo['password']);
                }
            }

            // Mettre à jour l'utilisateur si des données sont fournies
            if (!empty($userData)) {
                $compte->client->user->update($userData);
            }

            // Mettre à jour le client si des données sont fournies
            if (!empty($clientData)) {
                $compte->client->update($clientData);
            }

            // Invalidation du cache
            Cache::store('redis_no_tags')->clear();

            return $this->success(
                new CompteResource($compte->load('client.user')),
                'Client mis à jour avec succès'
            );
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compte}/bloquer",
     *     summary="Bloquer un compte épargne",
     *     description="Bloque un compte épargne actif pour une durée déterminée",
     *     operationId="bloquerCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="ID UUID du compte à bloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée"),
     *             @OA\Property(property="duree", type="integer", example=30),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, example="mois")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/BlocageResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte ne peut pas être bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seuls les comptes épargne actifs peuvent être bloqués"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le Compte avec l'ID spécifié n'existe pas")
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
    public function bloquer(BlocageCompteRequest $request, Compte $compte)
    {
        try {
            $validatedData = $request->validated();

            // Calculer la date de fin de blocage
            $dateDebut = now();
            $duree = $validatedData['duree'];
            $unite = $validatedData['unite'];

            if ($unite === 'mois') {
                $dateFin = $dateDebut->copy()->addMonths($duree);
            } else {
                $dateFin = $dateDebut->copy()->addDays($duree);
            }

            // Mettre à jour le compte
            $compte->update([
                'statut' => 'bloque',
                'motif_blocage' => $validatedData['motif'],
                'date_blocage' => $dateDebut,
                'date_deblocage_prevue' => $dateFin,
            ]);

            return $this->success(
                new BlocageResource($compte),
                'Compte bloqué avec succès'
            );
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compte}/debloquer",
     *     summary="Débloquer un compte",
     *     description="Débloque un compte précédemment bloqué",
     *     operationId="debloquerCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="ID UUID du compte à débloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Vérification complétée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/DeblocageResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte ne peut pas être débloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte doit être bloqué pour pouvoir être débloqué"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le Compte avec l'ID spécifié n'existe pas")
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
    public function debloquer(DeblocageCompteRequest $request, Compte $compte)
    {
        try {
            $validatedData = $request->validated();

            // Mettre à jour le compte
            $compte->update([
                'statut' => 'actif',
                'motif_blocage' => null,
                'date_blocage' => null,
                'date_deblocage_prevue' => null,
                'date_deblocage' => now(),
            ]);

            return $this->success(
                new DeblocageResource($compte),
                'Compte débloqué avec succès'
            );
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/comptes/{compte}",
     *     summary="Supprimer un compte bancaire",
     *     description="Effectue une suppression douce (soft delete) du compte et change son statut à 'ferme'",
     *     operationId="deleteCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="ID UUID du compte à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteDeleteResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le Compte avec l'ID spécifié n'existe pas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Impossible de supprimer le compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Impossible de supprimer un compte avec un solde positif")
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
    public function destroy(Compte $compte)
    {
        try {
            // Vérifier si le compte a un solde positif
//            if ($compte->solde > 0) {
//                return $this->error(
//                    'Impossible de supprimer un compte avec un solde positif',
//                    409
//                );
//            }

            // Mettre à jour le statut et la date de fermeture
            $compte->update([
                'statut' => 'ferme',
                'deleted_at' => now(),
            ]);

            // Effectuer le soft delete
            $compte->delete();

            return $this->success(
                new CompteDeleteResource($compte),
                'Compte supprimé avec succès'
            );
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire avec un client existant ou nouveau",
     *     operationId="createCompte",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "courant", "cheque"}, example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", format="float", minimum=10000, example=500000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="client", type="object",
     *                 oneOf={
     *                     @OA\Schema(
     *                         @OA\Property(property="id", type="string", format="uuid", description="ID du client existant"),
     *                         @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                         @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                         @OA\Property(property="telephone", type="string", example="771234567"),
     *                         @OA\Property(property="nci", type="string", example="1234567890123"),
     *                         @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
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
    public function store(StoreCompteRequest $request)
    {
        try {
            $compte = $this->compteService->creerCompte($request->validated());

            return $this->success(
                new CompteResource($compte),
                'Compte créé avec succès',
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Gestion des erreurs de base de données (contraintes d'unicité, etc.)
            if ($e->getCode() == 23000) { // Integrity constraint violation
                return $this->error('Une contrainte d\'unicité a été violée', 400);
            }
            return $this->error("Erreur de base de données : " . $e->getMessage(), 500);
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
