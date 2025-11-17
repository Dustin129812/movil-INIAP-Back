<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThOvertimeEntry extends Model
{
    use HasFactory;

    protected $table = 'th_overtime_entries';

    protected $fillable = [
        'overtime_report_id',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'activity_type_id',
        'vehicle_placa',
        'observations',
        'supplemental_minutes',
        'extraordinary_minutes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // --- Relaciones Eloquent ---

    /**
     * Obtiene el reporte mensual al que pertenece esta entrada.
     */
    public function report()
    {
        return $this->belongsTo(ThOvertimeReport::class, 'overtime_report_id');
    }

    /**
     * Obtiene el tipo de actividad (ej. "Movilización").
     */
    public function activityType()
    {
        return $this->belongsTo(ThActivityType::class, 'activity_type_id');
    }

    /**
     * Obtiene el vehículo utilizado.
     */
    public function vehicle()
    {
        return $this->belongsTo(ThVehicle::class, 'vehicle_placa');
    }
}
