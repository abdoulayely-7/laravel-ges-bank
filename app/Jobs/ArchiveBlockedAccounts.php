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
                    // Récupérer les données avant suppression
                    $compteData = $compte->toArray();
                    $clientData = $compte->client->toArray();
                    $userData = $compte->client->user->toArray();

                    // Récupérer les transactions
                    $transactions = Transaction::where('compte_id', $compte->id)->get();

                    // Déplacer vers Neon (supprimer de la base principale et créer dans Neon)
                    $this->moveToNeon('users', $userData);
                    $this->moveToNeon('clients', $clientData);
                    $this->moveToNeon('comptes', $compteData);

                    foreach ($transactions as $transaction) {
                        $this->moveToNeon('transactions', $transaction->toArray());
                    }

                    // Supprimer définitivement de la base principale
                    Transaction::where('compte_id', $compte->id)->delete();
                    $compte->forceDelete(); // Suppression définitive

                    DB::commit();
                    Log::info("Successfully moved account {$compte->id} to Neon archive");

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
     * Déplace les données vers la base Neon (suppression de la base principale)
     */
    private function moveToNeon(string $table, array $data): void
    {
        // Convertir les dates Carbon en string
        foreach ($data as $key => $value) {
            if ($value instanceof \Carbon\Carbon) {
                $data[$key] = $value->toDateTimeString();
            }
        }

        DB::connection('neon')->table($table)->insert($data);
    }
}
