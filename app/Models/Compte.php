<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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



    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected $appends = ['solde'];

    public function getSoldeAttribute(): float
    {
        // On calcule le solde à partir des transactions
        $totalDepot = $this->transactions()
            ->where('type', 'depot')
            ->sum('montant');

        $totalRetrait = $this->transactions()
            ->where('type', 'retrait')
            ->sum('montant');

        // Le solde = dépôts - retraits
        return (float) ($totalDepot - $totalRetrait);
    }

    // generer numero de compte
    protected function numeroCompte(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value,
            set: function ($value) {
                if ($value) {
                    // si un numéro est fourni manuellement, on le garde
                    return $value;
                }

                // Sinon, générer automatiquement
                $lastCompte = self::latest('created_at')->first();
                $lastNumber = $lastCompte ? intval(substr($lastCompte->numero_compte, 1)) : 0;
                $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

                return 'C' . $newNumber;
            }
        );
    }


    /**
     * Scope global pour récupérer uniquement les comptes non supprimés
     */
    protected static function booted(): void
    {
        static::addGlobalScope('nonSupprimes', function (Builder $builder) {
            $builder->whereNull('deleted_at');
        });
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
