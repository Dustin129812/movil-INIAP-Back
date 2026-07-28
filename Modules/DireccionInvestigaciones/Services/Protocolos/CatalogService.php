<?php

namespace Modules\DireccionInvestigaciones\Services\Protocolos;

use App\Models\User;
use App\Models\Crops;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\ResearchArea;
use Modules\Investigacion\Entities\Canton;

class CatalogService
{
    /**
     * Obtiene todos los catálogos necesarios para los formularios de protocolos.
     */
    public function getAllCatalogs(): array
    {
        return [
            'stations' => Location::select('id', 'name')->get(),

            'users'    => User::select('id', 'name', 'dni')->orderBy('name')->get(),

            'crops'    => Crops::with('productiveRubro:id,name')
                ->select('id', 'name', 'productive_rubro_id')
                ->orderBy('name')
                ->get(),

            'areas'    => ResearchArea::with('lines:id,research_area_id,name')
                ->select('id', 'name')
                ->get(),

            'cantons'  => Canton::select('id', 'name')->orderBy('name')->get(),
        ];
    }
}
