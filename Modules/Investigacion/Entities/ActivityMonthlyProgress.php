<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityMonthlyProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_monthly_progress';
    protected $fillable = [
        'activity_id',
        'month',
        'percentage',
        'observation',
    ];

    protected $casts = [
        'month' => 'date',
        'percentage' => 'decimal:2',
        'observation',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
