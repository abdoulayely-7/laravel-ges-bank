<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveBlockedAccounts implements ShouldQueue
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
        Log::info('Starting ArchiveBlockedAccounts job');

        try {
            // Récupérer tous les comptes bloqués
            $blockedAccounts = Compte::where('statut', 'bloque')->get();

            if ($blockedAccounts->isEmpty()) {
                Log::info('No blocked accounts found to archive');
                return;
            }

            Log::info("Found {$blockedAccounts->count()} blocked accounts to archive");

            foreach ($blockedAccounts as $compte) {
                DB::beginTransaction();

                try {
                    // Copier le compte vers Neon
                    $this->copyToNeon('comptes', $compte->toArray());

                    // Copier les transactions associées
                    $transactions = Transaction::where('compte_id', $compte->id)->get();
                    foreach ($transactions as $transaction) {
                        $this->copyToNeon('transactions', $transaction->toArray());
                    }

                    // Copier le client associé
                    $this->copyToNeon('clients', $compte->client->toArray());

                    // Copier l'utilisateur associé
                    $this->copyToNeon('users', $compte->client->user->toArray());

                    // Supprimer de la base principale
                    $compte->delete();

                    DB::commit();
                    Log::info("Successfully archived account {$compte->id}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to archive account {$compte->id}: " . $e->getMessage());
                    throw $e;
                }
            }

            Log::info('ArchiveBlockedAccounts job completed successfully');

        } catch (\Exception $e) {
            Log::error('ArchiveBlockedAccounts job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Copie les données vers la base Neon
     */
    private function copyToNeon(string $table, array $data): void
    {
        // Supprimer l'id pour éviter les conflits
        unset($data['id']);

        // Convertir les dates Carbon en string
        foreach ($data as $key => $value) {
            if ($value instanceof \Carbon\Carbon) {
                $data[$key] = $value->toDateTimeString();
            }
        }

        DB::connection('neon')->table($table)->insert($data);
    }
}
