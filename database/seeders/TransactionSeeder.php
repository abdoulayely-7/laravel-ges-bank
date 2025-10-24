<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On parcourt chaque compte existant
        Compte::all()->each(function ($compte) {
            // On génère entre 5 et 20 transactions aléatoires
            Transaction::factory()
                ->count(rand(5, 20))
                ->create([
                    'compte_id' => $compte->id,
                ]);
        });
    }
}
