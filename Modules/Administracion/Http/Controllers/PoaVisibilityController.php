<?php

namespace Modules\Administracion\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Investigacion\Entities\Rubro;
use Modules\TalentoHumano\Entities\AdministrativeUnit;

class PoaVisibilityController extends Controller
{
    /**
     * Obtener datos iniciales: Unidades y Rubros disponibles
     */
    public function index()
    {
        return response()->json([
            'units' => AdministrativeUnit::with('visibleRubros')->orderBy('name')->get(),
            'rubros' => Rubro::orderBy('name')->get()
        ]);
    }

    /**
     * Guardar la asignación
     */
    public function sync(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:th_administrative_units,id',
            'rubro_ids' => 'array',
            'rubro_ids.*' => 'exists:rubros,id'
        ]);

        $unit = AdministrativeUnit::findOrFail($request->unit_id);

        $unit->visibleRubros()->sync($request->rubro_ids);

        return response()->json(['message' => 'Permisos de visualización actualizados']);
    }
}
