<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// Importamos los modelos de los otros módulos
use App\Modules\Planificacion\Models\WeekActivity;
use Modules\Inventario\Entities\Batch;
use Modules\Inventario\Entities\Machinery;

class FieldLog extends Model
{
    use SoftDeletes;

    protected $table = 'field_logs';

    protected $fillable = [
        'week_activity_id', 'execution_date', 'duration_hours',
        'location_name', 'labor_cost', 'machinery_cost',
        'input_cost', 'total_cost', 'observations'
    ];

    // 1. Relación con lo Planificado (Tu código legacy/actual)
    public function activity()
    {
        return $this->belongsTo(WeekActivity::class, 'week_activity_id');
    }

    // 2. Relación con Insumos (Many-to-Many a través de tabla pivote custom)
    public function inputs()
    {
        return $this->belongsToMany(Batch::class, 'field_log_inputs', 'field_log_id', 'batch_id')
            ->withPivot('quantity_used', 'unit_cost_snapshot', 'total_line_cost')
            ->withTimestamps();
    }

    // 3. Relación con Maquinaria
    public function machinery()
    {
        return $this->belongsToMany(Machinery::class, 'field_log_machinery', 'field_log_id', 'machinery_id')
            ->withPivot('hours_used', 'hourly_cost_snapshot', 'total_line_cost')
            ->withTimestamps();
    }
}
