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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte')->unique();
            $table->enum('type', ['epargne', 'courant', 'cheque']);
            $table->string('devise')->default('FCFA');
            $table->timestamp('date_creation')->useCurrent();
            $table->foreignUuid('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('statut', ['actif', 'bloquer'])->default('actif');
            $table->string('motif_blocage')->nullable();

            $table->softDeletes();
            $table->timestamps();


            $table->index(['client_id', 'type', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
