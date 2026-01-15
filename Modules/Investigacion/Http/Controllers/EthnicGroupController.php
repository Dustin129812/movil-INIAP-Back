<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\Investigacion\Entities\Ethnic_Group;

// Asegúrate de que el nombre del modelo coincida

class EthnicGroupController extends Controller
{
    /**
     * Devuelve una lista de todos los grupos étnicos.
     */
    public function index()
    {
        $ethnicGroups = Ethnic_Group::orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $ethnicGroups]);
    }
}
