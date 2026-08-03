<?php

namespace Modules\AgroDecide\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HojaDato extends Model {

    protected $table = 'AgroDecide.hojas_datos';
    protected $fillable =
        [
            'visita_id',
            'nombre_plantilla',
            'datos_variables',
            'uuid_movil'
        ];

    protected $casts = ['datos_variables' => 'array'];
}
