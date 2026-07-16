<?php

namespace Modules\Notificaciones\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $table = 'notificaciones.notifications';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'id' => 'string',
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
