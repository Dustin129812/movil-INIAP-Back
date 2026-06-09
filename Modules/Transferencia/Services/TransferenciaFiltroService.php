<?php

namespace Modules\Transferencia\Services;

use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Entities\Parcela;
use Modules\Transferencia\Traits\ScopesByLocation;
use Illuminate\Support\Collection;

class TransferenciaFiltroService
{
    use ScopesByLocation;

    /**
     * Obtiene solo las provincias que tienen parcelas u organizaciones registradas por la estación.
     */
    public function getProvinciasActivas(int|string $locationId): Collection
    {
        $idsParcelas = $this->applyLocationScope(Parcela::query(), $locationId)
            ->select('provincia_id')
            ->distinct();

        $idsOrganizaciones = $this->applyLocationScope(Organizacion::query(), $locationId)
            ->select('provincia_id')
            ->distinct();

        $provinciasIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('provincia_id')
            ->filter();

        return Province::whereIn('id', $provinciasIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene solo los cantones activos de una provincia específica en la estación.
     */
    public function getCantonesActivos(int $provinciaId, int|string $locationId): Collection
    {
        $idsParcelas = $this->applyLocationScope(Parcela::where('provincia_id', $provinciaId), $locationId)
            ->select('canton_id')
            ->distinct();

        $idsOrganizaciones = $this->applyLocationScope(Organizacion::where('provincia_id', $provinciaId), $locationId)
            ->select('canton_id')
            ->distinct();

        $cantonesIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('canton_id')
            ->filter();

        return Canton::whereIn('id', $cantonesIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene solo las parroquias activas de un cantón específico en la estación.
     */
    public function getParroquiasActivas(int $cantonId, int|string $locationId): Collection
    {
        $idsParcelas = $this->applyLocationScope(Parcela::where('canton_id', $cantonId), $locationId)
            ->select('parroquia_id')
            ->distinct();

        $idsOrganizaciones = $this->applyLocationScope(Organizacion::where('canton_id', $cantonId), $locationId)
            ->select('parroquia_id')
            ->distinct();

        $parroquiasIds = $idsParcelas->union($idsOrganizaciones)
            ->pluck('parroquia_id')
            ->filter();

        return Parroquia::whereIn('id', $parroquiasIds)
            ->orderBy('nombre')
            ->get();
    }

    public function getEstaciones()
    {
        return Location::select('id', 'name as nombre')
            ->orderBy('name')
            ->get();
    }
}
