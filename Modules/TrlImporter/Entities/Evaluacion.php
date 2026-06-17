<?php

    namespace Modules\TrlImporter\Entities;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'trl.evaluaciones';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'tecnologia_id', 'fecha', 'tecnico', 'estado', 'observaciones'];

    public function respuestas()
    {
        return $this->hasMany(Respuesta::class, 'evaluacion_id');
    }
}
