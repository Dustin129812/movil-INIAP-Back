<?php

namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CicloCultivo extends Model {
    protected $table = 'kopia.ciclos_cultivo';
    protected $fillable =
        [
            'lote_id',
            'proyecto_id',
            'cultivo_variedad',
            'distancia_siembra',
            'fecha_siembra',
            'fecha_fin',
            'metricas_siembra',
            'es_actual',
            'uuid_movil',
            'metricas_siembra'
        ];

    protected $casts =
        [
            'fecha_siembra' => 'datetime',
            'metricas_siembra' => 'array',
            'fecha_fin' => 'date',
            'es_actual' => 'boolean'
        ];

    public function visitas(): HasMany {
        return $this->hasMany(Visita::class, 'ciclo_cultivo_id');
    }
}
