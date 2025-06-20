<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticSupport extends Model
{
    use HasFactory;
    protected $table = 'logistic_support';

    protected $fillable = [
        'name',
    ];

    public function weekActivities()
    {
        return $this->belongsToMany(
            related: WeekActivity::class,
            table: 'weekly_logistic',
            foreignPivotKey: 'logistic_support_id',
            relatedPivotKey: 'weekly_activities_id'
        )
        ->withTimestamps()
        ->withPivot('deleted_at');
    }
}
