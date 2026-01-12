<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeekPlanner extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'weekly_planners';
    protected $fillable = [
        'product_id',
        'week_activity_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function weekActivity()
    {
        return $this->belongsTo(WeekActivity::class, 'week_activity_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'activity_user', 'activity_id', 'user_id');
    }

}
