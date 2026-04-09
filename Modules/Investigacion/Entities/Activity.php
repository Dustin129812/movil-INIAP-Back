<?php

namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $table = 'activities';
    protected $fillable = [
        'description',
        'budget',
        'product_id',
        'accrued_budget'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'activity_user', 'activity_id', 'user_id')
            ->withTimestamps();
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function indicators()
    {
        return $this->belongsToMany(Performance_Indicator::class, 'activity_indicator', 'activity_id', 'indicator_id');
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
    public function weeklyActivities()
    {
        return $this->hasMany(WeekActivity::class);
    }

    public function monthlyExecutionProgress()
    {
        return $this->hasMany(ActivityExecutionProgress::class);
    }

}
