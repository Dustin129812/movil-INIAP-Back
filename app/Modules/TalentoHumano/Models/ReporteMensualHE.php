<?php

namespace App\Modules\TalentoHumano\HorasExtras\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReporteMensualHE extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre de la tabla explícito por el nombre largo del modelo
    protected $table = 'reporte_mensual_hes';

    protected $fillable = [
        'user_id',
        'mes',
        'anio',
        'total_horas_suplementarias',
        'total_horas_extraordinarias',
        'monto_suplementarias',
        'monto_extraordinarias',
        'monto_fondos_reserva',
        'monto_decimo_tercero',
        'monto_total_pagar',
        'estado',
        'jefe_id',
        'aprobado_jefe_at',
        'daf_id',
        'aprobado_daf_at',
    ];

    protected $casts = [
        'aprobado_jefe_at' => 'datetime',
        'aprobado_daf_at' => 'datetime',
        'total_horas_suplementarias' => 'decimal:2',
        // ... (puedes castear todos los montos a decimal:2)
        'monto_total_pagar' => 'decimal:2',
    ];

    /**
     * El conductor al que pertenece este reporte.
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El Jefe (Vladimir) que aprobó este reporte.
     */
    public function jefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    /**
     * El DAF (Majo) que aprobó este reporte.
     */
    public function daf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'daf_id');
    }

    /**
     * Todos los registros de horas individuales que componen este reporte mensual.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(RegistroHora::class, 'reporte_mensual_he_id');
    }
}
