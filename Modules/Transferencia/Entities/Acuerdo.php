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

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function ensayos(): HasMany
    {
        return $this->hasMany(Ensayo::class);
    }

    /**
     * Relación: Un acuerdo puede respaldar muchas parcelas.
     */
    public function parcelas()
    {
        return $this->hasMany(Parcela::class, 'acuerdo_id');
    }
}
