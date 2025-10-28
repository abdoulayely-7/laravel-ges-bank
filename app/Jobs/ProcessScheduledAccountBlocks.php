<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessScheduledAccountBlocks implements ShouldQueue
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
        Log::info('Starting ProcessScheduledAccountBlocks job');

        try {
            // Récupérer tous les comptes avec un blocage planifié arrivé à échéance
            $accountsToBlock = Compte::where('statut', 'actif')
                ->where('type', 'epargne') // Uniquement les comptes épargne
                ->whereNotNull('date_debut_blocage_planifiee')
                ->where('date_debut_blocage_planifiee', '<=', now())
                ->get();

            if ($accountsToBlock->isEmpty()) {
                Log::info('No accounts scheduled for blocking found');
                return;
            }

            Log::info("Found {$accountsToBlock->count()} accounts scheduled for blocking");

            foreach ($accountsToBlock as $compte) {
                DB::beginTransaction();

                try {
                    Log::info("Processing account {$compte->id} - Scheduled: {$compte->date_debut_blocage_planifiee}, Now: " . now());

                    // Calculer la date de fin de blocage
                    $startDate = \Carbon\Carbon::parse($compte->date_debut_blocage_planifiee);
                    $endDate = $this->calculateEndDate(
                        $startDate,
                        $compte->duree_blocage_valeur,
                        $compte->duree_blocage_unite
                    );

                    Log::info("Calculated end date: {$endDate} for account {$compte->id}");

                    // Mettre à jour le compte
                    $compte->update([
                        'statut' => 'bloque',
                        'date_debut_blocage' => $compte->date_debut_blocage_planifiee,
                        'date_fin_blocage_prevue' => $endDate,
                        'motif_blocage' => 'Blocage planifié automatique - ' . $compte->motif_blocage,
                    ]);

                    // Nettoyer les champs de planification
                    $compte->update([
                        'date_debut_blocage_planifiee' => null,
                        'duree_blocage_valeur' => null,
                        'duree_blocage_unite' => null,
                    ]);

                    DB::commit();
                    Log::info("Successfully blocked account {$compte->id} until {$endDate}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to block account {$compte->id}: " . $e->getMessage());
                    throw $e;
                }
            }

            Log::info('ProcessScheduledAccountBlocks job completed successfully');

        } catch (\Exception $e) {
            Log::error('ProcessScheduledAccountBlocks job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcule la date de fin de blocage
     */
    private function calculateEndDate(\Carbon\Carbon $startDate, int $value, string $unit): \Carbon\Carbon
    {
        return match ($unit) {
            'minutes' => $startDate->copy()->addMinutes($value),
            'heures' => $startDate->copy()->addHours($value),
            'jours' => $startDate->copy()->addDays($value),
            'mois' => $startDate->copy()->addMonths($value),
            default => $startDate->copy()->addDays($value),
        };
    }
}
