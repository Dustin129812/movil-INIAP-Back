<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityExecutionProgress extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'month', 'percentage'];

    protected $casts = [
        'month' => 'date',
        'percentage' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
