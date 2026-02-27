<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Investigacion\Entities\Location;

class Bodega extends Model
{
    use SoftDeletes;

    protected $table = 'produccion.bodegas';

    protected $fillable = [
        'location_id',
        'nombre',
        'descripcion'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function movimientosKardex()
    {
        return $this->hasMany(Kardex::class);
    }
}
