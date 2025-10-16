<?php

namespace App\Http\Controllers;

use App\Models\Area; // Este es el modelo para los cargos/posiciones
use Illuminate\Http\Request;

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
