<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';

    protected $fillable = [
        'user_id',
        'uuid',
        'modelo',
        'sistema_operativo',
        'ultimo_login',
    ];

    protected $casts = [
        'ultimo_login' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
