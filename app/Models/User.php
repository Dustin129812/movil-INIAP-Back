<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable  implements JWTSubject
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles;
    protected $guard_name = 'api';
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

    public function location()
    {
        return $this->belongsTo(Location::class);
    }


    public function ethnic_groups(){
        return $this->belongsTo(Ethnic_Group::class);
    }

    public function positions(){
        return $this->belongsTo(Position::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function activity(){
        return $this->hasMany(Activity::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_user', 'user_id', 'activity_id')
            ->withTimestamps();
    }

    public function createdWeekActivities()
    {
        return $this->hasMany(WeekActivity::class, 'user_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user');
    }

    public function weeklyPulses()
    {
        return $this->hasMany(WeeklyPulse::class);
    }

}
