<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
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
        Log::info('Starting UnarchiveExpiredBlockedAccounts job');

        try {
            // Récupérer tous les comptes archivés dont la date de déblocage prévue est dépassée
            $expiredAccounts = DB::connection('neon')
                ->table('comptes')
                ->where('statut', 'bloque')
                ->where('date_deblocage_prevue', '<', now())
                ->get();

            if ($expiredAccounts->isEmpty()) {
                Log::info('No expired blocked accounts found to unarchive');
                return;
            }

            Log::info("Found {$expiredAccounts->count()} expired blocked accounts to unarchive");

            foreach ($expiredAccounts as $archivedAccount) {
                DB::beginTransaction();

                try {
                    // Copier l'utilisateur depuis Neon vers la base principale
                    $userData = $this->getFromNeon('users', ['id' => $archivedAccount->client->user_id]);
                    if ($userData) {
                        User::create($userData);
                    }

                    // Copier le client depuis Neon vers la base principale
                    $clientData = $this->getFromNeon('clients', ['id' => $archivedAccount->client_id]);
                    if ($clientData) {
                        Client::create($clientData);
                    }

                    // Copier le compte depuis Neon vers la base principale
                    $compteData = (array) $archivedAccount;
                    $compteData['statut'] = 'actif'; // Remettre à actif
                    unset($compteData['motif_blocage']);
                    unset($compteData['date_blocage']);
                    unset($compteData['date_deblocage_prevue']);
                    unset($compteData['date_deblocage']);

                    Compte::create($compteData);

                    // Copier les transactions associées
                    $transactions = DB::connection('neon')
                        ->table('transactions')
                        ->where('compte_id', $archivedAccount->id)
                        ->get();

                    foreach ($transactions as $transaction) {
                        Transaction::create((array) $transaction);
                    }

                    // Supprimer de Neon
                    DB::connection('neon')->table('comptes')->where('id', $archivedAccount->id)->delete();

                    DB::commit();
                    Log::info("Successfully unarchived account {$archivedAccount->id}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to unarchive account {$archivedAccount->id}: " . $e->getMessage());
                    throw $e;
                }
            }

            Log::info('UnarchiveExpiredBlockedAccounts job completed successfully');

        } catch (\Exception $e) {
            Log::error('UnarchiveExpiredBlockedAccounts job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les données depuis la base Neon
     */
    private function getFromNeon(string $table, array $conditions): ?array
    {
        $record = DB::connection('neon')
            ->table($table)
            ->where($conditions)
            ->first();

        return $record ? (array) $record : null;
    }
}
