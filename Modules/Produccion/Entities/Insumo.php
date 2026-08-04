<?php

namespace Modules\Produccion\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insumo extends Model
{
    use SoftDeletes;
    protected $table = 'produccion.insumos';
    protected $fillable =
        [
            'unidad_medida_id',
            'tipo',
            'nombre',
            'descripcion'
        ];

    public function unidadMedida() {
        return $this->belongsTo(UnidadMedida::class);
    }
}
