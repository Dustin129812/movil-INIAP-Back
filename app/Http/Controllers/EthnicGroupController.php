<?php

namespace App\Http\Controllers;

use App\Models\Ethnic_Group; // Asegúrate de que el nombre del modelo coincida
use Illuminate\Http\Request;

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
