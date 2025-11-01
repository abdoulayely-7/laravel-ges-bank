<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur client avec un mot de passe connu
        User::create([
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_verified' => DB::raw('true'),
        ]);

        // Créer un utilisateur admin avec un mot de passe connu
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'email_verified_at' => now(),
            'is_verified' => DB::raw('true'),
        ]);

        // Créer quelques utilisateurs factices supplémentaires
        User::factory(3)->create();
    }
}
