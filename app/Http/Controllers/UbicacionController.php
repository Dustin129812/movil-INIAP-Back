<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Estacion;
use Illuminate\Http\JsonResponse;

class UbicacionController extends Controller
{
    public function provincias(): JsonResponse
    {
        
        $provincias = Provincia::query()
            ->where('activo', '=', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($provincia) {
                return [
                    'id' => $provincia->id,
                    'name' => $provincia->nombre,
                    'codigo' => $provincia->codigo,
                ];
            });

        return response()->json([
            'success' => true,
            'provincias' => $provincias,
        ]);
    }

    public function cantones(int $provinciaId): JsonResponse
    {
        $cantones = Canton::query()
            ->where('provincia_id', '=', $provinciaId)
            ->where('activo', '=', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($canton) {
                return [
                    'id' => $canton->id,
                    'name' => $canton->nombre,
                    'codigo' => $canton->codigo,
                    'provincia_id' => $canton->provincia_id,
                ];
            });

        return response()->json([
            'success' => true,
            'cantones' => $cantones,
        ]);
    }

    public function estaciones(): JsonResponse
    {
        $estaciones = Estacion::query()
            ->where('activo', '=', true)
            ->with('canton.provincia')
            ->orderBy('nombre')
            ->get()
            ->map(function ($estacion) {
                return [
                    'id' => $estacion->id,
                    'name' => $estacion->nombre,
                    'codigo' => $estacion->codigo,
                    'canton_id' => $estacion->canton_id,
                ];
            });

        return response()->json([
            'success' => true,
            'estaciones' => $estaciones,
        ]);
    }
}