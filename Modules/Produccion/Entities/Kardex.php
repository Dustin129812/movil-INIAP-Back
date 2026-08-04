<?php

namespace Modules\Produccion\Entities;
use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    protected $table = 'produccion.kardex';

    protected $fillable = [
        'bodega_id', 'insumo_id', 'tipo_movimiento', 'cantidad',
        'costo_unitario', 'costo_total', 'saldo_cantidad',
        'costo_promedio', 'documento_referencia', 'observaciones'
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'costo_unitario' => 'float',
        'costo_total'    => 'float',
        'saldo_cantidad' => 'float',
        'costo_promedio' => 'float',
    ];

    public function insumo() { return $this->belongsTo(Insumo::class); }
    public function bodega() { return $this->belongsTo(Bodega::class); }
}
