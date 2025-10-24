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
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address'); // Adresse IP de l'utilisateur
            $table->string('user_agent')->nullable(); // User agent du navigateur
            $table->string('endpoint'); // Endpoint appelé
            $table->string('method'); // Méthode HTTP (GET, POST, etc.)
            $table->integer('request_count'); // Nombre de requêtes dans la fenêtre
            $table->timestamp('window_start'); // Début de la fenêtre de temps
            $table->timestamp('window_end'); // Fin de la fenêtre de temps
            $table->boolean('blocked')->default(false); // Si l'utilisateur est bloqué
            $table->timestamp('blocked_until')->nullable(); // Date de fin de blocage
            $table->json('metadata')->nullable(); // Données supplémentaires (headers, etc.)
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index(['ip_address', 'window_start']);
            $table->index(['endpoint', 'method']);
            $table->index('blocked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
