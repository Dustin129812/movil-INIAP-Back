<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityMonthlyProgress extends Model
{
    use HasFactory;

    protected $table = 'activity_monthly_progress';
    protected $fillable = [
        'activity_id',
        'month',
        'percentage'
    ];

    protected $casts = [
        'month' => 'date',
        'percentage' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
