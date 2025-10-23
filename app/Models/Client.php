<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'user_id',
        'telephone',
        'adresse',
        'nci',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }
}
