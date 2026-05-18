<?php

namespace Modules\Kopia\Services;

use Modules\Kopia\Entities\Cultivo;
use Modules\Kopia\Entities\Variedad;

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
