<?php

namespace App\Modules\TalentoHumano\HorasExtras\Models;

use App\Models\User;
use App\Modules\TalentoHumano\Shared\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistroHora extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehiculo_id',
        'fecha',
        'horas_suplementarias',
        'horas_extraordinarias',
        'descripcion_actividad',
        'fecha_limite_registro',
        'estado',
        'aprobador_jefe_id',
        'aprobado_jefe_at',
        'rechazo_jefe_motivo',
        'aprobador_daf_id',
        'aprobado_daf_at',
        'rechazo_daf_motivo',
        'reporte_mensual_he_id', // <-- La FK que añadimos
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_limite_registro' => 'datetime',
        'aprobado_jefe_at' => 'datetime',
        'aprobado_daf_at' => 'datetime',
        'horas_suplementarias' => 'decimal:2',
        'horas_extraordinarias' => 'decimal:2',
    ];

    /**
     * El conductor que hizo este registro.
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El vehículo (placa) usado en este registro.
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * El jefe que aprobó/rechazó este registro.
     */
    public function aprobadorJefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_jefe_id');
    }

    /**
     * El DAF que aprobó/rechazó este registro.
     */
    public function aprobadorDaf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_daf_id');
    }

    /**
     * El reporte mensual al que pertenece este registro.
     */
    public function reporteMensual(): BelongsTo
    {
        return $this->belongsTo(ReporteMensualHE::class, 'reporte_mensual_he_id');
    }
}
