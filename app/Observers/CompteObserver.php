<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        // Générer le numéro de compte automatiquement avant la création
        if (!$compte->numero_compte) {
            $lastCompte = Compte::withTrashed()->latest('created_at')->first();
            $lastNumber = $lastCompte ? intval(substr($lastCompte->numero_compte, 1)) : 0;
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
            $compte->numero_compte = 'C' . $newNumber;
        }
    }

    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        $plainPassword = $compte->client->plainPassword ?? 'N/A';

        event(new CompteCreated($compte, $plainPassword));

        // Invalidation fine du cache avec tags
        Cache::tags(['comptes'])->flush();

        Log::info('Cache comptes invalidé après création', [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte
        ]);
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        // Invalidation du cache
        Cache::store('redis_no_tags')->clear();

        Log::info('Cache comptes invalidé après mise à jour', [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte
        ]);
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        // Invalidation du cache
        Cache::store('redis_no_tags')->clear();

        Log::info('Cache comptes invalidé après suppression', [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte
        ]);
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }
}
