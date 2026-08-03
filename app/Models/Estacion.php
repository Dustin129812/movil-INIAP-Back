<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estacion extends Model
{
    protected $table = 'estaciones';

    protected $fillable = [
        'canton_id',
        'nombre',
        'codigo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class, 'canton_id');
    }
}
