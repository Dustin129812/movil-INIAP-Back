<?php

namespace Modules\AgroDecide\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;

class Lote extends Model {
    protected $table = 'AgroDecide.lotes';

    protected $fillable = [
        'uuid_movil',
        'nombre_lote',
        'area',
        'ubicacion_manual',
        'condiciones_terreno',
        'province_id',
        'canton_id',
        'location_id',
        'otros_datos_geo',
        'altitud',
        'parroquia',
        'dispositivo_invitado_id',
        'estado_verificacion',
    ];

    protected $casts = [
        'condiciones_terreno' => 'array',
        'coordenadas' => 'array',
    ];

    // public function provincia(): BelongsTo {
    //     return $this->belongsTo(Province::class, 'province_id');
    // }

    // public function canton(): BelongsTo {
    //     return $this->belongsTo(Canton::class, 'canton_id');
    // }

    // public function estacion(): BelongsTo {
    //     return $this->belongsTo(Location::class, 'location_id');
    // }

    public function ciclos(): HasMany {
        return $this->hasMany(CicloCultivo::class, 'lote_id');
    }

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class, 'lote_id');
    }
}
