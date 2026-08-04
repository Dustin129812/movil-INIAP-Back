<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'user_id',
        'nombre_lote',
        'uuid_movil',
        'sync_status',
        'coordenadas',
        'ubicacion_manual',
        'provincia_id',
        'canton_id',
        'estacion_id',
    ];

    protected $casts = [
        'coordenadas' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class, 'canton_id');
    }

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(Estacion::class, 'estacion_id');
    }
}
