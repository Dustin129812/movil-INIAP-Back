<?php

namespace Modules\TrlImporter\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Trl\Entities\MatrizTrl;

class Respuesta extends Model
{
    protected $table = 'trl.respuestas';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'evaluacion_id',
        'matriz_trl_id',
        'cumple'
    ];

    protected $casts = [
        'cumple' => 'boolean',
    ];

    /**
     * Relación con la evaluación madre
     */
    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    /**
     * Relación con la pregunta específica de la matriz
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(MatrizTrl::class, 'matriz_trl_id');
    }
}
