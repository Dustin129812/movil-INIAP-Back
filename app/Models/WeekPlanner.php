<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekPlanner extends Model
{
    use HasFactory;
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
