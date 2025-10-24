<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numero_compte',
        'type',
        'devise',
        'client_id',
        'statut',
        'motif_blocage',
        'date_creation',
    ];

    /**
     * Scope global pour récupérer uniquement les comptes non supprimés
     */
    protected static function booted(): void
    {
        static::addGlobalScope('nonSupprimes', function (Builder $builder) {
            $builder->whereNull('deleted_at');
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope local pour récupérer un compte par son numéro
     */
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numero_compte', $numero);
    }

    /**
     * Scope local pour récupérer les comptes d'un client basé sur son téléphone
     */
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
