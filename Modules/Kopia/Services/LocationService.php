<?php

namespace Modules\Kopia\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Investigacion\Entities\Location;

class LocationService
{
    /**
     * Obtiene el listado completo de ubicaciones.
     */
    public function obtenerTodasLasUbicaciones(): Collection
    {
        return Location::all();
    }
}
