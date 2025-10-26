<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;

        // Essayer plusieurs fois de récupérer le solde correct
        $maxAttempts = 10;
        $attempt = 0;

        do {
            sleep(1); // Attendre 1 seconde entre chaque tentative
            $compte = $compte->fresh(['client.user', 'transactions']);
            $attempt++;

            Log::info("Tentative {$attempt} - Solde: " . $compte->solde . ", Transactions: " . $compte->transactions()->count());
        } while ($compte->solde == 0 && $attempt < $maxAttempts);

        // Si après toutes les tentatives le solde est toujours 0, utiliser le solde initial
        if ($compte->solde == 0) {
            // Essayer de récupérer le solde initial depuis les données de la requête
            // ou utiliser une valeur par défaut
            Log::warning('Solde toujours à 0 après ' . $maxAttempts . ' tentatives, utilisation du solde initial');
        }

        // Vérifier que l'email existe
        if (!$compte->client || !$compte->client->user || !$compte->client->user->email) {
            Log::warning('Impossible d\'envoyer l\'email de création de compte : email manquant', [
                'compte_id' => $compte->id,
                'client_id' => $compte->client_id ?? null,
                'user_id' => $compte->client->user_id ?? null,
            ]);
            return;
        }

        try {
            // Debug: Log du solde avant envoi
            Log::info('Préparation envoi email', [
                'compte_id' => $compte->id,
                'solde_calcule' => $compte->getSoldeAttribute(),
                'solde_accessor' => $compte->solde,
                'transactions_count' => $compte->transactions()->count(),
                'transactions' => $compte->transactions->map(function($t) {
                    return ['type' => $t->type, 'montant' => $t->montant];
                }),
            ]);

            // Envoi du mail
            Mail::to($compte->client->user->email)->send(new CompteCreeMail($compte));

            Log::info('Email de création de compte envoyé avec succès', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
                'solde' => $compte->solde,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de création de compte', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
                'solde' => $compte->solde,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
