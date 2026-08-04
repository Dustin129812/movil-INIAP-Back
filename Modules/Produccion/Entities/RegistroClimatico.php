<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class RegistroClimatico extends Model
{
    protected $table = 'produccion.registros_climaticos';
    protected $fillable =
        [
            'libro_campo_id',
            'fecha_registro',
            'temperatura',
            'humedad',
            'precipitacion',
            'viento_velocidad',
            'nubosidad',
            'notas_clima'
    ];

    public function libroCampo()
    {
        return $this->belongsTo(LibroCampo::class);
    }
}
