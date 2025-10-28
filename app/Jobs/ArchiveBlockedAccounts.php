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
        // Créer la table si elle n'existe pas
        $this->createTableIfNotExists($table);

        // Nettoyer les données selon la table
        $data = $this->cleanDataForTable($table, $data);

        // Convertir les dates Carbon en string
        foreach ($data as $key => $value) {
            if ($value instanceof \Carbon\Carbon) {
                $data[$key] = $value->toDateTimeString();
            }
        }

        try {
            DB::connection('neon')->table($table)->insert($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si la clé existe déjà, on fait un upsert (update or insert)
            if (str_contains($e->getMessage(), 'duplicate key value')) {
                Log::warning("Record already exists in {$table}, skipping: " . $data['id'] ?? 'unknown');
                return;
            }
            throw $e;
        }
    }

    /**
     * Nettoie les données selon la table (gère les champs obligatoires)
     */
    private function cleanDataForTable(string $table, array $data): array
    {
        switch ($table) {
            case 'users':
                // Pour les users, s'assurer que password n'est pas null
                if (!isset($data['password']) || $data['password'] === null) {
                    $data['password'] = bcrypt('archived_' . time()); // Mot de passe temporaire
                }
                // Supprimer les champs timestamps s'ils sont null
                if (!isset($data['email_verified_at']) || $data['email_verified_at'] === null) {
                    unset($data['email_verified_at']);
                }
                break;

            case 'clients':
                // Supprimer les champs qui peuvent être null
                $nullableFields = ['telephone', 'adresse', 'nci'];
                foreach ($nullableFields as $field) {
                    if (!isset($data[$field]) || $data[$field] === null) {
                        unset($data[$field]);
                    }
                }
                break;

            case 'comptes':
                // Supprimer les champs qui peuvent être null
                $nullableFields = ['motif_blocage', 'date_blocage', 'date_deblocage_prevue', 'date_deblocage'];
                foreach ($nullableFields as $field) {
                    if (!isset($data[$field]) || $data[$field] === null) {
                        unset($data[$field]);
                    }
                }
                break;

            case 'transactions':
                // Supprimer les champs qui peuvent être null
                $nullableFields = ['description', 'reference_externe'];
                foreach ($nullableFields as $field) {
                    if (!isset($data[$field]) || $data[$field] === null) {
                        unset($data[$field]);
                    }
                }
                break;
        }

        return $data;
    }

    /**
     * Crée la table dans Neon si elle n'existe pas
     */
    private function createTableIfNotExists(string $table): void
    {
        $tableExists = DB::connection('neon')->select("SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = ?
        )", [$table]);

        if (!$tableExists[0]->exists) {
            // Créer la table en copiant la structure depuis la base principale
            $createTableSQL = $this->getCreateTableSQL($table);
            if ($createTableSQL) {
                DB::connection('neon')->statement($createTableSQL);
                Log::info("Created table {$table} in Neon database");
            }
        }
    }

    /**
     * Génère le SQL CREATE TABLE en se basant sur la structure existante
     */
    private function getCreateTableSQL(string $table): ?string
    {
        try {
            // Définir les structures de tables manuellement pour éviter les erreurs
            $tableSchemas = [
                'users' => 'CREATE TABLE "users" (
                    "id" UUID PRIMARY KEY,
                    "name" VARCHAR(255) NOT NULL,
                    "email" VARCHAR(255) NOT NULL,
                    "email_verified_at" TIMESTAMP NULL,
                    "password" VARCHAR(255) NOT NULL,
                    "remember_token" VARCHAR(100) NULL,
                    "created_at" TIMESTAMP NULL,
                    "updated_at" TIMESTAMP NULL
                )',

                'clients' => 'CREATE TABLE "clients" (
                    "id" UUID PRIMARY KEY,
                    "user_id" UUID NOT NULL,
                    "telephone" VARCHAR(255) NULL,
                    "adresse" TEXT NULL,
                    "nci" VARCHAR(255) NULL,
                    "created_at" TIMESTAMP NULL,
                    "updated_at" TIMESTAMP NULL,
                    FOREIGN KEY ("user_id") REFERENCES "users"("id")
                )',

                'comptes' => 'CREATE TABLE "comptes" (
                    "id" UUID PRIMARY KEY,
                    "numero_compte" VARCHAR(255) NOT NULL,
                    "type" VARCHAR(255) NOT NULL,
                    "devise" VARCHAR(255) NOT NULL,
                    "date_creation" TIMESTAMP NOT NULL,
                    "client_id" UUID NOT NULL,
                    "statut" VARCHAR(255) NOT NULL,
                    "motif_blocage" VARCHAR(255) NULL,
                    "date_blocage" TIMESTAMP NULL,
                    "date_deblocage_prevue" TIMESTAMP NULL,
                    "date_deblocage" TIMESTAMP NULL,
                    "deleted_at" TIMESTAMP NULL,
                    "created_at" TIMESTAMP NULL,
                    "updated_at" TIMESTAMP NULL,
                    FOREIGN KEY ("client_id") REFERENCES "clients"("id")
                )',

                'transactions' => 'CREATE TABLE "transactions" (
                    "id" UUID PRIMARY KEY,
                    "compte_id" UUID NOT NULL,
                    "type" VARCHAR(255) NOT NULL,
                    "montant" DECIMAL(15,2) NOT NULL,
                    "solde_apres" DECIMAL(15,2) NOT NULL,
                    "description" TEXT NULL,
                    "reference_externe" VARCHAR(255) NULL,
                    "created_at" TIMESTAMP NULL,
                    "updated_at" TIMESTAMP NULL,
                    FOREIGN KEY ("compte_id") REFERENCES "comptes"("id")
                )'
            ];

            return $tableSchemas[$table] ?? null;

        } catch (\Exception $e) {
            Log::error("Failed to create table {$table}: " . $e->getMessage());
            return null;
        }
    }
}
