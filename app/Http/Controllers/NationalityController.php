<?php

namespace App\Http\Controllers;

use App\Models\Nationality;
use Illuminate\Http\Request;

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
