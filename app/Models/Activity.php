<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;
    protected $table = 'activities';
    protected $fillable = [
        'description',
        'budget',
        'product_id',
        'indicator_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'activity_user', 'activity_id', 'user_id')
            ->withTimestamps();
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function indicator(){
        return $this->belongsTo(Performance_Indicator::class);
    }

    public function weekActivities()
    {
        return $this->hasMany(WeekActivity::class);
    }

    public function monthlyProgress()
    {
        return $this->hasMany(ActivityMonthlyProgress::class);
    }

    public function executionProgress()
    {
        return $this->hasMany(ActivityExecutionProgress::class);
    }

    public function getMonthlyProgressPercentages(): array
    {
        return $this->monthlyProgress->map(function ($progress) {
            return [
                'month' => $progress->month->format('Y-m'),
                'percentage' => $progress->percentage,
                'absolute_percentage' => $this->ponderacion * ($progress->percentage / 100),
            ];
        })->toArray();
    }

}
