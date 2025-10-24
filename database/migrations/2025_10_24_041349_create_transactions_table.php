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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('compte_id')
                ->constrained('comptes')
                ->onDelete('cascade');

            $table->enum('type', ['depot', 'retrait']);

            // 💵 Montant (jusqu’à 999 999 999 999.99)
            $table->decimal('montant', 15, 2);

            $table->string('description')->nullable();

            $table->enum('statut', ['pending', 'complete', 'failed'])
                ->default('complete');

            $table->timestamp('date')->useCurrent();

            $table->timestamps();

            $table->index('compte_id');
            $table->index('type');
            $table->index('statut');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
