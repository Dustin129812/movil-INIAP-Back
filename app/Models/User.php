<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
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

    protected $attributes = [
        'multidisciplinary_group_id' => 1,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function multidisciplinary_groups(){
        return $this->belongsTo(Multidisciplinary_Group::class);
    }

    public function pei(){
        return $this->hasMany(Pei::class);
    }

}
