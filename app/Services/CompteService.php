<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Compte;
use App\Http\Resources\CompteResource;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompteService
{
    public function rechercherEtPaginer(array $params): array
    {
        //  Génération d'une clé de cache unique basée sur les paramètres
        $cacheKey = 'comptes_' . md5(json_encode($params));

        // Durée du cache (en secondes) - 5 minutes pour données moins volatiles
        $ttl = 300; // Augmenté pour mieux profiter du cache

        //  Utilisation du cache simple (sans tags pour compatibilité)
        return Cache::store('redis_no_tags')->remember($cacheKey, $ttl, function () use ($params, $cacheKey, $ttl) {
            Log::info('Cache miss - Génération des données comptes', ['params' => $params]);
            $query = Compte::with('client.user');

            // Filtrer par client si spécifié (pour les clients qui ne voient que leurs comptes)
            if (!empty($params['client_id'])) {
                $query->where('client_id', $params['client_id']);
            }

            // Filtres par défaut : comptes épargne et chèque actifs uniquement
            $defaultTypes = ['epargne', 'cheque'];
            $defaultStatut = 'actif';

            // Appliquer le filtre type : utiliser la valeur fournie ou les types par défaut
            if (!empty($params['type'])) {
                $query->where('type', $params['type']);
            } else {
                $query->whereIn('type', $defaultTypes);
            }

            // Appliquer le filtre statut : utiliser la valeur fournie ou le statut actif par défaut
            if (!empty($params['statut'])) {
                $query->where('statut', $params['statut']);
            } else {
                $query->where('statut', $defaultStatut);
            }


            if (!empty($params['search'])) {
                $s = strtolower($params['search']);
                $query->where(function ($q) use ($s) {
                    $q->whereRaw('LOWER(numero_compte) LIKE ?', ["%{$s}%"])
                        ->orWhereHas('client.user', function ($q2) use ($s) {
                            $q2->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"]);
                        });
                });
            }

            switch ($params['sort'] ?? '') {
                case 'dateCreation':
                    $query->orderBy('date_creation', $params['order'] ?? 'asc');
                    break;
                case 'solde':
                    $query->orderBy('solde', $params['order'] ?? 'asc');
                    break;
                case 'titulaire':
                    $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                        ->join('users', 'clients.user_id', '=', 'users.id')
                        ->orderByRaw("LOWER(users.name) " . ($params['order'] ?? 'asc'))
                        ->select('comptes.*');
                    break;
            }

            $paginator = $query->paginate(
                $params['limit'] ?? 10,
                ['*'],
                'page',
                $params['page'] ?? 1
            );

            $result = [
                'items' => CompteResource::collection($paginator->items()),
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'totalPages' => $paginator->lastPage(),
                    'totalItems' => $paginator->total(),
                    'itemsPerPage' => $paginator->perPage(),
                    'hasNext' => $paginator->hasMorePages(),
                    'hasPrevious' => $paginator->currentPage() > 1
                ],
                'links' => [
                    'self' => url()->current() . '?' . http_build_query(request()->query()),
                    'next' => $paginator->nextPageUrl(),
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage())
                ]
            ];

            Log::info('Données comptes générées et mises en cache', [
                'total_items' => $paginator->total(),
                'cache_key' => $cacheKey,
                'ttl' => $ttl
            ]);

            return $result;
        });
    }

    /**
     * Créer un nouveau compte bancaire avec transaction
     */
    public function creerCompte(array $data): Compte
    {
        return DB::transaction(function () use ($data) {
            // 1. Vérifier si le client existe ou en créer un nouveau
            $client = $this->trouverOuCreerClient($data['client']);

            // 2. Créer le compte
            $compte = Compte::create([
                'type' => $data['type'],
                'devise' => $data['devise'],
                'client_id' => $client->id,
                'statut' => 'actif',
                'date_creation' => now(),
            ]);

            // Récupérer les valeurs temporaires générées (non persistées)
            $plainPassword = $client->plainPassword ?? null;
            $verificationCode = $client->verificationCode ?? null;

            // 3. Créer la transaction initiale de dépôt
            $transaction = $compte->transactions()->create([
                'type' => 'depot',
                'montant' => $data['soldeInitial'],
                'description' => 'Ouverture de compte - dépôt initial',
                'statut' => 'complete',
                'date' => now(),
            ]);

            // Recharger le compte avec les relations pour recalculer le solde
            $compte = $compte->fresh(['client.user', 'transactions']);

            // Attacher en mémoire (non persisté) le mot de passe et le code de vérification
            // afin que l'observer / listeners qui reçoivent l'instance puissent y accéder
            if ($plainPassword !== null) {
                $compte->plainPassword = $plainPassword;
            }

            if ($verificationCode !== null) {
                $compte->verificationCode = $verificationCode;
            }

            // Attacher aussi le solde initial
            $compte->solde_initial_temp = $data['soldeInitial'];

            return $compte;
        });
    }

    /**
     * Trouver un client existant ou en créer un nouveau
     */
    private function trouverOuCreerClient(array $clientData): Client
    {
        // Si un ID de client est fourni, vérifier qu'il existe
        if (!empty($clientData['id'])) {
            return Client::findOrFail($clientData['id']);
        }

        // Chercher le client par téléphone ou email
        $client = Client::where('telephone', $clientData['telephone'])
            ->orWhereHas('user', function ($query) use ($clientData) {
                $query->where('email', $clientData['email']);
            })
            ->first();

        if ($client) {
            return $client;
        }

        // Générer un mot de passe temporaire
        $plainPassword = Str::random(8); // Mot de passe lisible

        // Générer un code de vérification à 6 chiffres
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Créer un nouvel utilisateur
        $user = User::create([
            'name' => $clientData['titulaire'],
            'email' => $clientData['email'],
            'password' => bcrypt($plainPassword), // Hash du mot de passe
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(30), // Expire dans 30 minutes
            'is_verified' => DB::raw('false'),
        ]);



        // Créer le client
        $client = Client::create([
            'user_id' => $user->id,
            'telephone' => $clientData['telephone'],
            'adresse' => $clientData['adresse'],
            'nci' => $clientData['nci'],
        ]);

        // Attacher temporairement le mot de passe et le code de vérification en mémoire
        $client->plainPassword = $plainPassword;
        $client->verificationCode = $verificationCode;

        return $client;
    }
}
