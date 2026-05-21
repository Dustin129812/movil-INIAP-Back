<?php

namespace Modules\Transferencia\Services;

use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Entities\Parcela;
use Illuminate\Support\Collection;

class TransferenciaFiltroService
{
    /**
     * Obtiene solo las provincias que tienen parcelas u organizaciones registradas.
     */
    public function getProvinciasActivas(): Collection
    {
        $idsParcelas = Parcela::select('provincia_id')->distinct();
        $idsOrganizaciones = Organizacion::select('provincia_id')->distinct();

        $provinciasIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('provincia_id')
            ->filter();

        return Province::whereIn('id', $provinciasIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene solo los cantones activos de una provincia específica.
     */
    public function getCantonesActivos(int $provinciaId): Collection
    {
        $idsParcelas = Parcela::where('provincia_id', $provinciaId)->select('canton_id')->distinct();
        $idsOrganizaciones = Organizacion::where('provincia_id', $provinciaId)->select('canton_id')->distinct();

        $cantonesIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('canton_id')
            ->filter();

        return Canton::whereIn('id', $cantonesIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene solo las parroquias activas de un cantón específico.
     */
    public function getParroquiasActivas(int $cantonId): Collection
    {
        $idsParcelas = Parcela::where('canton_id', $cantonId)->select('parroquia_id')->distinct();
        $idsOrganizaciones = Organizacion::where('canton_id', $cantonId)->select('parroquia_id')->distinct();

        $parroquiasIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('parroquia_id')
            ->filter();

        return Parroquia::whereIn('id', $parroquiasIds)
            ->orderBy('nombre') // Ajusta 'nombre' o 'name' según tu BD
            ->get();
    }
}
