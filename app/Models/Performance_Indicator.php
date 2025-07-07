<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Performance_Indicator extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'performance_indicators';
    protected $fillable = [
        'name'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function weekActivities()
    {
        return $this->belongsToMany(
            WeekActivity::class,
            'weekly_indicators',
            'performance_indicators_id',
            'weekly_activities_id');
    }
}
