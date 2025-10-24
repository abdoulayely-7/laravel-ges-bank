<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'description',
        'statut',
        'date',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
