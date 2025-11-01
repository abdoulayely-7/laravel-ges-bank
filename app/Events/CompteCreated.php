<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $compte;
    public string $plainPassword;
    public string $verificationCode;
    public float $soldeInitial;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte, string $plainPassword, string $verificationCode, float $soldeInitial)
    {
        $this->compte = $compte;
        $this->plainPassword = $plainPassword;
        $this->verificationCode = $verificationCode;
        $this->soldeInitial = $soldeInitial;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
