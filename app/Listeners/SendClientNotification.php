<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreeMail;
use App\Services\Sms\SmsSenderInterface;
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
    protected SmsSenderInterface $smsService;

    public function __construct(SmsSenderInterface $smsService)
    {
        $this->smsService = $smsService;
    }


    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;
        $client = $compte->client;
        $telephone = $client->telephone;
        $plainPassword = $event->plainPassword ?? 'N/A';
        $verificationCode = $event->verificationCode ?? 'N/A';
        $soldeInitial = $event->soldeInitial ?? 0;

        // Les données sont maintenant disponibles depuis l'événement (calculées dans l'Observer)
        Log::info("Données depuis événement - Code: {$verificationCode}, Password: {$plainPassword}, Solde: {$soldeInitial}");

        // Pour l'instant, on utilise le solde depuis les transactions si disponible, sinon 0
        if ($compte->transactions && $compte->transactions->count() > 0) {
            $soldeInitial = $compte->transactions->where('type', 'depot')->sum('montant') -
                           $compte->transactions->where('type', 'retrait')->sum('montant');
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
            // Envoi du mail avec le mot de passe et le code de vérification
            Mail::to($compte->client->user->email)->send(new CompteCreeMail($compte, $plainPassword, $verificationCode));

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


        // // 🔹 Envoi du SMS avec le code de vérification
        // try {
        //     $message = "Bienvenue chez ECSA BANK ! Votre compte {$compte->numero_compte} a été créé avec succès. Solde initial: {$soldeInitial} FCFA. Code de vérification: {$verificationCode}";

        //     Log::info('Tentative envoi SMS', [
        //         'telephone' => $telephone,
        //         'message' => $message,
        //         'verificationCode' => $verificationCode,
        //         'soldeInitial' => $soldeInitial,
        //         'event_verificationCode' => $event->verificationCode ?? 'NULL',
        //         'client_verificationCode' => $client->verificationCode ?? 'NULL'
        //     ]);

        //     $result = $this->smsService->send($telephone, $message);

        //     if ($result) {
        //         Log::info('SMS envoyé avec succès', [
        //             'compte_id' => $compte->id,
        //             'telephone' => $telephone,
        //             'numero_compte' => $compte->numero_compte,
        //             'solde' => $compte->solde
        //         ]);
        //     } else {
        //         Log::warning('Échec envoi SMS', [
        //             'compte_id' => $compte->id,
        //             'telephone' => $telephone
        //         ]);
        //     }
        // } catch (\Exception $e) {
        //     Log::error('Erreur envoi SMS : ' . $e->getMessage(), [
        //         'compte_id' => $compte->id,
        //         'telephone' => $telephone
        //     ]);
        // }
    }
}
