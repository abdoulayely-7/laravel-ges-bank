<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiRateLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'endpoint',
        'method',
        'request_count',
        'window_start',
        'window_end',
        'blocked',
        'blocked_until',
        'metadata',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'blocked_until' => 'datetime',
        'blocked' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Vérifier si l'utilisateur est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->blocked && $this->blocked_until && $this->blocked_until->isFuture();
    }

    /**
     * Bloquer l'utilisateur pour une durée donnée
     */
    public function blockForMinutes(int $minutes): void
    {
        $this->update([
            'blocked' => true,
            'blocked_until' => Carbon::now()->addMinutes($minutes),
        ]);
    }

    /**
     * Débloquer l'utilisateur
     */
    public function unblock(): void
    {
        $this->update([
            'blocked' => false,
            'blocked_until' => null,
        ]);
    }

    /**
     * Incrémenter le compteur de requêtes
     */
    public function incrementRequestCount(): void
    {
        $this->increment('request_count');
    }

    /**
     * Scope pour récupérer les utilisateurs bloqués
     */
    public function scopeBlocked($query)
    {
        return $query->where('blocked', true)
                    ->where('blocked_until', '>', Carbon::now());
    }

    /**
     * Scope pour récupérer les enregistrements d'une fenêtre de temps donnée
     */
    public function scopeInTimeWindow($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('window_start', [$start, $end]);
    }
}
