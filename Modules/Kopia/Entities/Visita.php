<?php

namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visita extends Model {

    protected $table = 'kopia.visitas';
    protected $fillable =
        [
            'ciclo_cultivo_id',
            'tecnico_nombre',
            'fecha_visita',
            'observaciones',
            'recomendaciones',
            'uuid_movil',
            'proyecto_id'
        ];
    public function hojasDatos()
    {
        return $this->hasMany(HojaDato::class, 'visita_id');
    }
}
