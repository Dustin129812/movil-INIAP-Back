<?php

namespace Modules\Transferencia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ensayo extends Model
{
    use SoftDeletes;

    protected $table = 'transferencia.ensayos';

    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'nombre_tecnologia',
        'tipo_tecnologia',
        'tiene_protocolo',
        'aprobado_por_comite',
        'fecha_aprobacion_protocolo',
        'archivo_protocolo_path',
        'archivo_informe_path',
        'producto_id',
        'actividad_id',
    ];

    protected $casts = [
        'tiene_protocolo' => 'boolean',
        'aprobado_por_comite' => 'boolean',
        'fecha_aprobacion_protocolo' => 'date',
    ];

    /**
     * Relación Muchos a Muchos con Usuarios (Equipo Técnico)
     */
    public function equipoTecnico(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'transferencia.ensayo_user',
            'ensayo_id',
            'user_id'
        );
    }

    public function parcelas()
    {
        return $this->hasMany(Parcela::class, 'ensayo_id'); // Asegúrate de que el foreign key sea el correcto si no es 'ensayo_id'
    }
}
