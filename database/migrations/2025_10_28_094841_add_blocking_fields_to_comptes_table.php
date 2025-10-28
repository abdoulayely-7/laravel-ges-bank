<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            // Champs pour la planification du blocage
            $table->timestamp('date_debut_blocage_planifiee')->nullable();
            $table->integer('duree_blocage_valeur')->nullable();
            $table->enum('duree_blocage_unite', ['minutes', 'heures', 'jours', 'mois'])->nullable();

            // Champs pour le blocage effectif
            $table->timestamp('date_debut_blocage')->nullable();
            $table->timestamp('date_fin_blocage_prevue')->nullable();
            $table->timestamp('date_fin_blocage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn([
                'date_debut_blocage_planifiee',
                'duree_blocage_valeur',
                'duree_blocage_unite',
                'date_debut_blocage',
                'date_fin_blocage_prevue',
                'date_fin_blocage'
            ]);
        });
    }
};
