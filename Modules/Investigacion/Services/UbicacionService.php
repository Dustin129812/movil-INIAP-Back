<?php

namespace Modules\Investigacion\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;

class UbicacionService
{
    public function getAllProvincias(): Collection
    {
        return Province::orderBy('name')->get(['id', 'name']);
    }

    public function getCantonesByProvincia(int $provinciaId): Collection
    {
        return Canton::where('provincia_id', $provinciaId)->orderBy('name')->get(['id', 'name']);
    }

    public function getParroquiasByCanton(int $cantonId): Collection
    {
        return Parroquia::where('canton_id', $cantonId)->orderBy('nombre')->get(['id', 'nombre']);
    }
}
