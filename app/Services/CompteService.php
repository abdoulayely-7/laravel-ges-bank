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

        // 🔍 Filtres
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['statut'])) {
            $query->where('statut', $params['statut']);
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
}
