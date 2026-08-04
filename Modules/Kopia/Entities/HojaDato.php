<?php

namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HojaDato extends Model {

    protected $table = 'kopia.hojas_datos';
    protected $fillable =
        [
            'visita_id',
            'nombre_plantilla',
            'datos_variables',
            'uuid_movil'
        ];

    protected $casts = ['datos_variables' => 'array'];
}
