<?php

namespace Modules\AgroDecide\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserAgroDecide extends Model implements JWTSubject
{
    protected $table = 'AgroDecide.users';

    protected $fillable = [
        'correo_institucional',
        'password',
        'nombre',
        'estado',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => 'user',
            'correo' => $this->correo_institucional,
        ];
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
