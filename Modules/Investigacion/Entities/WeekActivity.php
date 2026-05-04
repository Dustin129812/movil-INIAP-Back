<?php

namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Administracion\Entities\Dispatch;

class WeekActivity extends Model
{
    use SoftDeletes;
    protected $table = 'weekly_activities';
    protected $fillable = [
        'description',
        'date',
        'percentage',
        'material_id',
        'activity_id',
        'observations',
        'work_location',
        'user_id',
        'status',
        'evidence_path',
        'activity_type',
    ];

    protected $casts = [
        'evidence_path' => 'array',
    ];

    public function dispatch()
    {
        return $this->hasOne(Dispatch::class, 'week_activity_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function weekPlanner()
    {
        return $this->hasOne(WeekPlanner::class, 'week_activity_id');
    }

    public function activity(){
        return $this->belongsTo(Activity::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_week_activity')
            ->withPivot('quantity', 'description')
            ->withTimestamps();
    }

    public function logisticSupports()
    {
        return $this->belongsToMany(
            LogisticSupport::class,
            'weekly_logistic',
            'weekly_activities_id',
            'logistic_support_id'
        )
        ->withTimestamps()
        ->withPivot('deleted_at');
    }
    public function performanceIndicators()
    {
        return $this->belongsToMany(Performance_Indicator::class, 'weekly_indicators', 'weekly_activities_id', 'performance_indicators_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function logisticSupportUsers()
    {
        return $this->belongsToMany(
            User::class,
            'week_activity_logistic_support_user',
            'weekly_activity_id',
            'user_id'
        )
            ->withPivot('status')
            ->withTimestamps();
    }
}
