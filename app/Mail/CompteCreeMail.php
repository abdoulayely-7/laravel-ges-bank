<?php

namespace App\Mail;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Compte $compte;
    public string $plainPassword;
    public string $verificationCode;

    /**
     * Create a new message instance.
     */
    public function __construct(Compte $compte, string $plainPassword, string $verificationCode)
    {
        $this->compte = $compte;
        $this->plainPassword = $plainPassword;
        $this->verificationCode = $verificationCode;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte a été créé avec succès'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.compte.cree',
            with: [
                'compte' => $this->compte,
                'plainPassword' => $this->plainPassword,
                'verificationCode' => $this->verificationCode,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
