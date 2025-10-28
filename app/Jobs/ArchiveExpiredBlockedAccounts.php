<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Récupérer tous les comptes bloqués dont la date de déblocage prévue est dépassée
        $expiredAccounts = Compte::where('statut', 'bloque')
            ->where('date_deblocage_prevue', '<', now())
            ->get();

        $archivedCount = 0;

        foreach ($expiredAccounts as $compte) {
            try {
                // Archiver le compte (soft delete)
                $compte->update([
                    'statut' => 'ferme',
                    'deleted_at' => now(),
                ]);

                $compte->delete(); // Soft delete

                // Ici vous pourriez aussi archiver les transactions liées
                // $compte->transactions()->update(['archived' => true]);

                $archivedCount++;

                Log::info("Compte archivé automatiquement", [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'date_deblocage_prevue' => $compte->date_deblocage_prevue,
                ]);

            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage du compte", [
                    'compte_id' => $compte->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Archivage automatique terminé", [
            'comptes_archives' => $archivedCount,
            'total_comptes_expired' => $expiredAccounts->count(),
        ]);
    }
}
