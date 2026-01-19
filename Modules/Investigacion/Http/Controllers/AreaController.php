<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\Investigacion\Entities\Position;


class AreaController extends Controller
{
    /**
     * Devuelve una lista de todas las áreas/cargos.
     */
    public function index()
    {
        $positions = Position::orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $positions]);
    }
}
