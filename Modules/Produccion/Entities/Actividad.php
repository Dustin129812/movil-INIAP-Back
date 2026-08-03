<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model {
    protected $table = 'produccion.actividades';
    protected $fillable =
        [
            'libro_campo_id',
            'kardex_id',
            'fecha',
            'labor',
            'cantidad_insumo',
            'costo_actividad',
            'observaciones'
        ];

    public function libroCampo()
    {
        return $this->belongsTo(LibroCampo::class);
    }
    public function movimientoKardex()
    {
        return $this->belongsTo(Kardex::class, 'kardex_id');
    }
}
