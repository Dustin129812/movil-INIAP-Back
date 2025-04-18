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
        'user_id',
        'product_id',
        'indicator_id',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function indicator(){
        return $this->belongsTo(Performance_Indicator::class);
    }

    public function weekActivity(){
        return $this->hasMany(WeekActivity::class);
    }
}
