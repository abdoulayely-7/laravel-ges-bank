<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client password grant pour l'API
        \Laravel\Passport\Client::create([
            'user_id' => null,
            'name' => 'ECSA Bank API Client',
            'secret' => 'client_secret_' . \Illuminate\Support\Str::random(32),
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => DB::raw('false'),
            'password_client' => DB::raw('true'),
            'revoked' => DB::raw('false'),
        ]);

        // Créer un client personal access pour les tests/développement
        \Laravel\Passport\Client::create([
            'user_id' => null,
            'name' => 'ECSA Bank Personal Access Client',
            'secret' => 'personal_secret_' . \Illuminate\Support\Str::random(32),
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => DB::raw('true'),
            'password_client' => DB::raw('false'),
            'revoked' => DB::raw('false'),
        ]);
    }
}
