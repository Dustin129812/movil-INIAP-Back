<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable  implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'dni',
        'name',
        'email',
        'password',
        'birth_date',
        'gender',
        'phone',
        'location_id',
        'nationality_id',
        'ethnic_id',
        'position_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function locations(){
        return $this->belongsTo(Location::class);
    }

    public function nationalities(){
        return $this->belongsTo(Nationality::class);
    }

    public function ethnic_groups(){
        return $this->belongsTo(Ethnic_Group::class);
    }

    public function positions(){
        return $this->belongsTo(Position::class);
    }

    public function leaderGroups()
    {
        return $this->hasMany(Multidisciplinary_Group::class, 'leader_id');
    }

    // Relación con los grupos donde el usuario es miembro
    public function memberGroups()
    {
        return $this->belongsToMany(Multidisciplinary_Group::class, 'group_members', 'user_id', 'group_id');
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function activity(){
        return $this->hasMany(Activity::class);
    }

}
