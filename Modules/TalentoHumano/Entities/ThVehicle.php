<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThVehicle extends Model
{
    use HasFactory;

    protected $table = 'th_vehicles';
    protected $primaryKey = 'placa';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'placa',
        'model',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
