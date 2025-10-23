<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Pour chaque client existant, créer 2 comptes
        Client::all()->each(function ($client) {
            Compte::factory(2)->create([
                'client_id' => $client->id,
            ]);
        });
    }
}
