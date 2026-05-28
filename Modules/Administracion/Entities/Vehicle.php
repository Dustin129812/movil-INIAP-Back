<?php

namespace Modules\Administracion\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Investigacion\Entities\Location;

class Vehicle extends Model
{
    protected $table = 'administracion.vehicles';

    protected $fillable = [
        'location_id',
        'plate',
        'brand',
        'model',
        'is_active'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
