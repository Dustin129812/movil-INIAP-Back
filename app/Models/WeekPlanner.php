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

    public function product(){
        return $this->belongsTo(Product::class);
    }

    PUBLIC function weekActivity(){
        return $this->belongsTo(WeekActivity::class);
    }
}
