<?php

namespace App\Services\Sms;

interface SmsSenderInterface
{
    /**
     * Envoie un message SMS au destinataire donné.
     *
     * @param string $to Numéro de téléphone (format international)
     * @param string $message Contenu du SMS
     * @return bool Succès ou échec
     */
    public function send(string $to, string $message): bool;
}
