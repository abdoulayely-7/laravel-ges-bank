<?php

namespace App\Models;

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
}
