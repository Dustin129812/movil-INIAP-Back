<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyIndicators extends Model
{
     use HasFactory;

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
