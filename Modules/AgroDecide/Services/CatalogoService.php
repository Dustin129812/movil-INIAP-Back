<?php

namespace Modules\AgroDecide\Services;

use Modules\AgroDecide\Entities\Cultivo;
use Modules\AgroDecide\Entities\Variedad;

class CatalogoService
{
    public function obtenerCatalogosCompletos()
    {
        return Cultivo::with('variedades')->get();
    }

    public function crearCultivo(array $data): Cultivo
    {
        return Cultivo::create($data);
    }

    public function crearVariedad(array $data): Variedad
    {
        return Variedad::create($data);
    }
}
