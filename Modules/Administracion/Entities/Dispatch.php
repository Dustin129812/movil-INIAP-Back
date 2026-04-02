<?php

namespace Modules\Administracion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Investigacion\Entities\WeekActivity;

class Dispatch extends Model
{
    protected $table = 'administracion.dispatches';

    protected $fillable = [
        'week_activity_id',
        'admin_id',
        'status',
        'requested_items',
        'dispatched_items',
        'admin_notes'
    ];

    protected $casts = [
        'requested_items' => 'array',
        'dispatched_items' => 'array',
    ];

    public function weekActivity(): BelongsTo
    {
        return $this->belongsTo(WeekActivity::class, 'week_activity_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
