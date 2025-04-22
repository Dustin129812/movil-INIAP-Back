<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekActivity extends Model
{
    use HasFactory;
    protected $table = 'weekly_activities';
    protected $fillable = [
        'description',
        'date',
        'material',
        'activity_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function weekPlanner()
    {
        return $this->hasOne(WeekPlanner::class, 'week_activity_id');
    }

    public function activity(){
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
