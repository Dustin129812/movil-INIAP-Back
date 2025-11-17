<?php

namespace Modules\TalentoHumano\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\TalentoHumano\Entities\ThOvertimeEntry;

class ThOvertimeReport extends Model
{
    use HasFactory;

    protected $table = 'th_overtime_reports';

    protected $fillable = [
        'driver_id',
        'month',
        'year',
        'status',
        'rejection_reason',
        'supervisor_approver_id',
        'daf_approver_id',
        'submitted_at',
        'supervisor_approved_at',
        'daf_approved_at',
        'rmu_at_submission',
        'hour_value',
        'total_supplemental_minutes',
        'total_extraordinary_minutes',
        'total_supplemental_usd',
        'total_extraordinary_usd',
        'decimo_tercero_usd',
        'fondos_reserva_usd',
        'total_usd_pay',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
        'daf_approved_at' => 'datetime',
        'rmu_at_submission' => 'decimal:2',
        'hour_value' => 'decimal:4',
        'total_supplemental_usd' => 'decimal:2',
        'total_extraordinary_usd' => 'decimal:2',
        'decimo_tercero_usd' => 'decimal:2',
        'fondos_reserva_usd' => 'decimal:2',
        'total_usd_pay' => 'decimal:2',
    ];

    // --- Relaciones Eloquent ---

    /**
     * Relación: El conductor que hizo el reporte.
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Relación: El supervisor que aprobó.
     */
    public function supervisorApprover()
    {
        return $this->belongsTo(User::class, 'supervisor_approver_id');
    }

    /**
     * Relación: El DAF que aprobó.
     */
    public function dafApprover()
    {
        return $this->belongsTo(User::class, 'daf_approver_id');
    }

    /**
     * Relación: Todas las entradas (viajes) del reporte.
     */
    public function entries()
    {
        return $this->hasMany(ThOvertimeEntry::class, 'overtime_report_id');
    }
}
