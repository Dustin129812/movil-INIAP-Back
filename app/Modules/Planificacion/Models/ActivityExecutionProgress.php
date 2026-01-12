<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityExecutionProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['activity_id', 'month', 'percentage', 'observation'];

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
