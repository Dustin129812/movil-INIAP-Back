<?php

namespace Modules\Transferencia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Modules\Transferencia\Entities\Parcela;

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

    // A futuro: Un catálogo de ensayo tiene muchas ejecuciones en campo (Parcelas)
    /*
    public function parcelas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Parcela::class);
    }
    */
}
