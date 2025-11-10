<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyIndicators extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'weekly_indicators';
    protected $fillable = [
        'weekly_activities_id',
        'performance_indicators_id',
    ];

    // Definir las relaciones
    public function weeklyActivity()
    {
        return $this->belongsTo(WeekActivity::class);
    }

    public function performanceIndicator()
    {
        return $this->belongsTo(Performance_Indicator::class);
    }

}
