<?php

namespace App\Models;

use App\Modules\Planificacion\Models\Activity;
use App\Modules\Planificacion\Models\Conversation;
use App\Modules\Planificacion\Models\Document;
use App\Modules\Planificacion\Models\Ethnic_Group;
use App\Modules\Planificacion\Models\Group;
use App\Modules\Planificacion\Models\Location;
use App\Modules\Planificacion\Models\Nationality;
use App\Modules\Planificacion\Models\Position;
use App\Modules\Planificacion\Models\Product;
use App\Modules\Planificacion\Models\WeekActivity;
use App\Modules\Planificacion\Models\WeeklyPulse;
use App\Modules\TalentoHumano\HorasExtras\Models\RegistroHora;
use App\Modules\TalentoHumano\HorasExtras\Models\ReporteMensualHE;
use App\Modules\TalentoHumano\Shared\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

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
        'sueldo',
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

    public function readConversations()
    {
        return $this->belongsToMany(Conversation::class)->withPivot('last_read_at')->withTimestamps();
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // --- RELACIONES DEL MÓDULO DE TALENTO HUMANO ---

    /**
     * Los vehículos (placas) que este usuario (conductor) tiene asignados.
     */
    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'conductor_vehiculo');
    }

    /**
     * Todos los registros de horas que este usuario (conductor) ha creado.
     */
    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'user_id');
    }

    /**
     * Todos los reportes mensuales generados para este usuario (conductor).
     */
    public function reportesMensualesHE()
    {
        return $this->hasMany(ReporteMensualHE::class, 'user_id');
    }
}
