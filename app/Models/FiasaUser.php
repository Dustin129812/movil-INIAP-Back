<?php

namespace App\Models;

use App\Modules\Planificacion\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class FiasaUser extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name ='api';
    protected $table = 'fiasa_users';
    protected $fillable = [
        'name',
        'dni',
        'email',
        'password',
        'location_id',
    ];

    protected $hidden = [
        'password',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
