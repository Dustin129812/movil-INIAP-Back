<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ActividadMaquinaria extends Model
{
    protected $table = 'produccion.actividades_maquinaria';
    protected $fillable =
        [
            'libro_campo_id',
            'maquinaria_id',
            'fecha',
            'horas_uso',
            'costo_total'
        ];

    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class);
    }
}
