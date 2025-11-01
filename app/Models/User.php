<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_code',
        'verification_code_expires_at',
        'is_verified',
    ];


    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }


    public function getRoleAttribute()
    {
        if ($this->client) return 'client';
        if ($this->admin) return 'admin';
        return 'user';
    }

    public function getClaims()
    {
        return [
            'role' => $this->role,
        ];
    }



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'verification_code_expires_at' => 'datetime',
    ];
}
