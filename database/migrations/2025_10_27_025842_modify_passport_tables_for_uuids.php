<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier la colonne user_id pour supporter les UUIDs
        DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE VARCHAR(255)');

        // Modifier aussi les autres tables Passport si nécessaire
        DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE oauth_clients ALTER COLUMN user_id TYPE VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre les colonnes en bigint (nécessite conversion)
        // Attention: Cette opération peut échouer si des données existent
        try {
            DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE BIGINT USING user_id::bigint');
            DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE BIGINT USING user_id::bigint');
            DB::statement('ALTER TABLE oauth_clients ALTER COLUMN user_id TYPE BIGINT USING user_id::bigint');
        } catch (\Exception $e) {
            // En cas d'erreur, on ne fait rien (données incompatibles)
        }
    }
};
