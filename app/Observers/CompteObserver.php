<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
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
        // Invalidation fine du cache avec tags
        Cache::tags(['comptes'])->flush();

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
        // Invalidation fine du cache avec tags
        Cache::tags(['comptes'])->flush();

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
