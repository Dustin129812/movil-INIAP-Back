<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maquinaria extends Model
{
    use SoftDeletes;
    protected $table = 'produccion.maquinaria';
    protected $fillable =
        ['nombre',
            'placa_serie',
            'costo_hora',
            'estado'
        ];
}
