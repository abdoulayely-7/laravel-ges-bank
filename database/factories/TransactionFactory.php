<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // On tire au hasard un compte existant
        $compte = Compte::inRandomOrder()->first();

        return [
            'id' => Str::uuid(),
            'compte_id' => $compte ? $compte->id : Compte::factory(),
            'type' => $this->faker->randomElement(['depot', 'retrait']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000), // entre 1 000 et 100 000 FCFA
            'description' => $this->faker->sentence(4),
            'statut' => $this->faker->randomElement(['pending', 'complete', 'failed']),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
