<?php

namespace Modules\Inventario\Entities;

use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    protected $table = 'inv_machinery';

    protected $fillable = [
        'name', 'type', 'acquisition_cost', 'useful_life_years',
        'acquisition_year', 'cost_parameters', 'annual_usage_hours',
        'calculated_hourly_cost'
    ];

    // Castear el JSON automáticamente a Array asociativo
    protected $casts = [
        'cost_parameters' => 'array',
        'is_active' => 'boolean'
    ];
}
