<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Investigacion\Entities\Location;


class Lote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produccion.lotes';

    protected $fillable = [
        'location_id',
        'parent_id',
        'codigo',
        'nombre',
        'superficie_hectareas',
        'estado',
        'observaciones',
        'poligono'
    ];

    protected $casts = [
        'superficie_hectareas' => 'float',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function hijos()
    {
        return $this->hasMany(Lote::class, 'parent_id');
    }
}
