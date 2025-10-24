<?php

namespace App\Services;

use App\Models\Compte;
use App\Http\Resources\CompteResource;
use Illuminate\Pagination\LengthAwarePaginator;

class CompteService
{
    public function rechercherEtPaginer(array $params): array
    {
        $query = Compte::with('client.user');

        // 🔍 Filtres - Le scope global nonSupprimes est automatiquement appliqué
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['statut'])) {
            $query->where('statut', $params['statut']);
        }

        // 🎯 Filtre spécial : comptes de type "cheque" OU "epargne" ET statut "actif"
        if (!empty($params['actifs_epargne_cheque'])) {
            $query->whereIn('type', ['cheque', 'epargne'])
                ->where('statut', 'actif');
        }

        // 🔍 Recherche par nom ou numéro de compte
        if (!empty($params['search'])) {
            $s = strtolower($params['search']);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(numero_compte) LIKE ?', ["%{$s}%"])
                    ->orWhereHas('client.user', function ($q2) use ($s) {
                        $q2->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"]);
                    });
            });
        }

        // 🔽 Tri
        switch ($params['sort']) {
            case 'dateCreation':
                $query->orderBy('date_creation', $params['order']);
                break;
            case 'solde':
                $query->orderBy('solde', $params['order']);
                break;
            case 'titulaire':
                $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                    ->join('users', 'clients.user_id', '=', 'users.id')
                    ->orderByRaw("LOWER(users.name) {$params['order']}")
                    ->select('comptes.*');
                break;
        }

        // 📄 Pagination
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($params['limit'], ['*'], 'page', $params['page']);

        return [
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
    }

    /**
     * Créer un nouveau compte bancaire
     */
    public function creerCompte(array $data): Compte
    {
        // 1. Vérifier si le client existe
        $client = $this->trouverOuCreerClient($data['client']);

        // 2. Créer le compte
        $compte = Compte::create([
            'numero_compte' => null, // Sera généré automatiquement par le mutateur
            'type' => $data['type'],
            'devise' => $data['devise'],
            'client_id' => $client->id,
            'statut' => 'actif',
            'date_creation' => now(),
        ]);

        // 3. Créer la transaction initiale de dépôt
        $compte->transactions()->create([
            'type' => 'depot',
            'montant' => $data['soldeInitial'],
            'description' => 'Ouverture de compte - dépôt initial',
            'statut' => 'complete',
            'date' => now(),
        ]);

        return $compte->load('client.user');
    }

    /**
     * Trouver un client existant ou en créer un nouveau
     */
    private function trouverOuCreerClient(array $clientData): \App\Models\Client
    {
        // Si un ID de client est fourni, vérifier qu'il existe
        if (!empty($clientData['id'])) {
            return \App\Models\Client::findOrFail($clientData['id']);
        }

        // Chercher le client par téléphone ou email
        $client = \App\Models\Client::where('telephone', $clientData['telephone'])
            ->orWhereHas('user', function ($query) use ($clientData) {
                $query->where('email', $clientData['email']);
            })
            ->first();

        if ($client) {
            return $client;
        }

        // Créer un nouvel utilisateur
        $user = \App\Models\User::create([
            'name' => $clientData['titulaire'],
            'email' => $clientData['email'],
            'password' => bcrypt(\Illuminate\Support\Str::random(12)), // Mot de passe généré
        ]);

        // Créer le client
        return \App\Models\Client::create([
            'user_id' => $user->id,
            'telephone' => $clientData['telephone'],
            'adresse' => $clientData['adresse'],
            'nci' => $clientData['nci'],
        ]);
    }
}
