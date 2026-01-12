<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Models\Nationality;

class NationalityController extends Controller
{
    /**
     * Devuelve una lista de todas las nacionalidades.
     */
    public function index()
    {
        // Seleccionamos solo 'id' y 'name' que es lo que necesita el frontend
        $nationalities = Nationality::orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $nationalities]);
    }
}
