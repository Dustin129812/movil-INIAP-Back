<?php

namespace Modules\Transferencia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;
use Modules\Investigacion\Entities\Canton;

class Parcela extends Model
{
    use SoftDeletes;

    protected $table = 'transferencia.parcelas';

    protected $fillable = [
        'ensayo_id',
        'organizacion_id',
        'acuerdo_id',
        'libro_campo_id',
        'location_id',

        'nombre',
        'provincia_id',
        'canton_id',
        'parroquia_id',
        'localidad',

        'coordenada_x',
        'coordenada_y',

        'fecha_implementacion',
        'fecha_finalizacion',
        'estado',
    ];

    protected $casts = [
        'fecha_implementacion' => 'date',
        'fecha_finalizacion' => 'date',
    ];

    // --- RELACIONES ESTRATÉGICAS ---

    public function ensayo(): BelongsTo
    {
        return $this->belongsTo(Ensayo::class);
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function acuerdo(): BelongsTo
    {
        return $this->belongsTo(Acuerdo::class);
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }

    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
