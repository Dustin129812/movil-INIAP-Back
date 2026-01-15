<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\Investigacion\Entities\Area;

// Este es el modelo para los cargos/posiciones

class AreaController extends Controller
{
    /**
     * Devuelve una lista de todas las áreas/cargos.
     */
    public function index()
    {
        $areas = Area::orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $areas]);
    }
}
