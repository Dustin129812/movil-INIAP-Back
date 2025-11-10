<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoveltyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'description',
        'observations',
        'date',
        'work_location',
        'percentage',
        'status',
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
        return $this->belongsToMany(Material::class, 'novelty_activity_material')
            ->withPivot('quantity', 'description')
            ->withTimestamps();
    }

    public function indicators()
    {
        return $this->belongsToMany(Performance_Indicator::class, 'novelty_activity_performance_indicator', 'novelty_activity_id', 'performance_indicator_id');
    }

    public function logisticSupport()
    {
        return $this->belongsToMany(User::class, 'novelty_activity_logistic_support', 'novelty_activity_id', 'user_id');
    }
}
