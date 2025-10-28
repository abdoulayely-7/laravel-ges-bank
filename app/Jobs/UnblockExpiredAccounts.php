<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnblockExpiredAccounts implements ShouldQueue
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
        // mais qui n'ont pas encore été archivés automatiquement
        $accountsToUnblock = Compte::where('statut', 'bloque')
            ->where('date_deblocage_prevue', '<', now())
            ->whereNull('deleted_at') // S'assurer qu'ils ne sont pas déjà archivés
            ->get();

        $unblockedCount = 0;

        foreach ($accountsToUnblock as $compte) {
            try {
                // Débloquer le compte automatiquement
                $compte->update([
                    'statut' => 'actif',
                    'motif_blocage' => null,
                    'date_blocage' => null,
                    'date_deblocage_prevue' => null,
                    'date_deblocage' => now(),
                ]);

                $unblockedCount++;

                Log::info("Compte débloqué automatiquement", [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'date_deblocage_prevue' => $compte->date_deblocage_prevue,
                    'date_deblocage' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error("Erreur lors du déblocage automatique du compte", [
                    'compte_id' => $compte->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Déblocage automatique terminé", [
            'comptes_debloques' => $unblockedCount,
            'total_comptes_to_unblock' => $accountsToUnblock->count(),
        ]);
    }
}
