<?php

namespace Modules\Transferencia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;

class Organizacion extends Model
{
    use SoftDeletes;

    protected $table = 'transferencia.organizaciones';

    protected $fillable = [
        'nombre',
        'tipo_organizacion',
        'participantes_hombres',
        'participantes_mujeres',
        'provincia_id',
        'canton_id',
        'parroquia_id',
        'location_id',
    ];

    protected $casts = [
        'participantes_hombres' => 'integer',
        'participantes_mujeres' => 'integer',
    ];

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

    public function acuerdos(): HasMany
    {
        return $this->hasMany(Acuerdo::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
