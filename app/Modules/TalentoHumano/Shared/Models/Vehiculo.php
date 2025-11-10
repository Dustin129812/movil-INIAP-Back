<?php

namespace App\Modules\TalentoHumano\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'placa',
        'descripcion',
    ];

    /**
     * Los conductores que tienen asignado este vehículo.
     */
    public function conductores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conductor_vehiculo');
    }
}
