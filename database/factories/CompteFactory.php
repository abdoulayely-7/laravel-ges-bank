<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['epargne', 'courant', 'cheque'];
    $statuts = ['actif', 'bloquer'];

    return [
        'id' => Str::uuid(),
        'numero_compte' => strtoupper(Str::random(10)),
        'type' => fake()->randomElement($types),
        'devise' => 'FCFA',
        'date_creation' => now(),
        'client_id' => Client::factory(),
        'statut' => fake()->randomElement($statuts),
        'motif_blocage' => null,
    ];
    }
}
