<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'produccion.unidades_medida';

    protected $fillable = [
        'nombre',
        'abreviatura'
    ];

    public function insumos()
    {
        return $this->hasMany(Insumo::class);
    }
}
