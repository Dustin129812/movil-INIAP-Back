<?php

namespace Modules\Transferencia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acuerdo extends Model
{
    use SoftDeletes;

    protected $table = 'transferencia.acuerdos';

    protected $fillable = [
        'organizacion_id',
        'fecha_firma',
        'anios_vigencia',
        'archivo_acuerdo_path',
    ];

    protected $casts = [
        'fecha_firma' => 'date',
        'anios_vigencia' => 'integer',
    ];

    // Relación hacia su Organización
    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    // Relación hacia los Ensayos vinculados a este acuerdo
    public function ensayos(): HasMany
    {
        return $this->hasMany(Ensayo::class);
    }
}
