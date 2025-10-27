<?php

namespace App\Services\Sms;

use  Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioSmsSender implements SmsSenderInterface
{
    private Client $client;
    private string $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->from = config('services.twilio.from');
    }

    public function send(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info("✅ SMS envoyé à $to");
            return true;
        } catch (\Exception $e) {
            Log::error("❌ Erreur envoi SMS à $to : {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Normalise le numéro au format international (+221 pour le Sénégal par défaut)
     */
    private function normalizePhone(string $telephone): string
    {
        // Retirer tous les espaces et caractères non numériques sauf "+"
        $telephone = preg_replace('/[^0-9+]/', '', $telephone);

        // Si le numéro commence déjà par "+", on ne touche à rien
        if (str_starts_with($telephone, '+')) {
            return $telephone;
        }

        // Si le numéro commence par "00", on le convertit en "+"
        if (str_starts_with($telephone, '00')) {
            return '+' . substr($telephone, 2);
        }

        // Si le numéro commence par "7" ou "6", on suppose que c’est un numéro sénégalais
        if (preg_match('/^(7|6)\d{8}$/', $telephone)) {
            return '+221' . $telephone;
        }

        // Dernier recours : on préfixe par +221 par défaut
        return '+221' . ltrim($telephone, '0');
    }
}
