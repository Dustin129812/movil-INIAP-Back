<?php

namespace Modules\AgroDecide\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AgroDecide\Entities\Lote;
use Modules\AgroDecide\Http\Requests\StoreLoteProyectoRequest;
use Modules\AgroDecide\Transformers\LoteResource;
use Tymon\JWTAuth\Facades\JWTAuth;
// use Modules\AgroDecide\Services\LoteService; <-- ¡Recuerda importar la clase si está en otra carpeta!

class LoteController extends Controller
{
    public function __construct(
        private readonly LoteService $loteService
    ) {}

    public function index()
    {
        $lotes = Lote::with(['proyectos'])
            ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
            ->get();

        return LoteResource::collection($lotes);
    }

    public function show($id)
    {
        $lote = $this->loteService->obtenerDetalleLote($id);

        return new LoteResource($lote);
    }

    public function store(StoreLoteProyectoRequest $request)
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $role = $payload->get('role');
            $sub = $payload->get('sub');

            // 1. Cláusula de guarda (Guard clause): Detiene todo si no es un usuario válido
            // Puedes agregar otros roles en el array si los administradores también pueden crear lotes
            if (!in_array($role, ['user']) || empty($sub)) {
                return response()->json([
                    'message' => 'Acceso denegado: Se requiere un usuario válido para registrar un lote.'
                ], 403);
            }

            $responsableId = (int) $sub;

            // 2. Llamada segura al servicio
            $lote = $this->loteService->crearLoteIntegrado(
                $request->validated(),
                $responsableId
            );

            // 3. Consulta limpia usando Eloquent
            $loteRefrescado = Lote::with(['proyectos.colaboradores'])
                ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
                ->findOrFail($lote->id); // Usar findOrFail es más seguro

            return response()->json([
                'data'    => new LoteResource($loteRefrescado),
                'message' => 'Lote experimental y proyectos creados de forma integrada con éxito.'
            ], 201);

        } catch (\Exception $e) {
            // Un error 500 es más semántico para excepciones del servidor que un 422
            return response()->json(['message' => 'Error al crear el lote: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre_lote' => 'required|string|max:150',
        ]);

        try {
            $lote = $this->loteService->actualizarLote($id, $validated);

            $loteRefrescado = Lote::select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
                ->findOrFail($lote->id);

            return response()->json([
                'data' => new LoteResource($loteRefrescado),
                'message' => 'Lote actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->loteService->eliminarLote($id);
            return response()->json(['message' => 'Lote eliminado exitosamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
}