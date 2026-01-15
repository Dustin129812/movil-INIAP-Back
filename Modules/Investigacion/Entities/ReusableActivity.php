<?php

namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReusableActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'name',
        'description',
        'work_location',
        'observations',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'reusable_activity_material')
            ->withPivot('quantity', 'description');
    }

    public function performanceIndicators()
    {
        return $this->belongsToMany(
            Performance_Indicator::class,
            'reusable_activity_performance_indicator',
            'reusable_activity_id',
            'performance_indicator_id'
        );
    }

    public function logisticSupportUsers()
    {
        return $this->belongsToMany(User::class, 'reusable_activity_logistic_support', 'reusable_activity_id', 'user_id');
    }
}
