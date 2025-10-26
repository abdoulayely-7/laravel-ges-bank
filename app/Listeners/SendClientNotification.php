<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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

        // Charger les relations nécessaires
        $compte->load('client.user');

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
            // Envoi du mail
            Mail::to($compte->client->user->email)->send(new CompteCreeMail($compte));

            Log::info('Email de création de compte envoyé avec succès', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de création de compte', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
