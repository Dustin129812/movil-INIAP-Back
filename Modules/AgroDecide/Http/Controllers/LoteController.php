<?php

namespace Modules\AgroDecide\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AgroDecide\Entities\Lote;
use Modules\AgroDecide\Http\Requests\StoreLoteProyectoRequest;
use Modules\AgroDecide\Services\LoteService;
use Modules\AgroDecide\Transformers\LoteResource;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function show($id): JsonResponse
    {
        try {
            $lote = $this->loteService->obtenerDetalleLote($id);
            return response()->json([
                'success' => true,
                'data' => new LoteResource($lote),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lote no encontrado.',
            ], 404);
        }
    }

    public function store(StoreLoteProyectoRequest $request): JsonResponse
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $role = $payload->get('role');
            $sub = $payload->get('sub');

            // Verificar que esté autenticado (user o guest) y tenga identificador
            if (empty($sub) || !in_array($role, ['user', 'guest'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado: Usuario no válido.',
                ], 403);
            }

            $lote = $this->loteService->crearLoteIntegrado($request->validated(), $sub, $role);

            return response()->json([
                'success' => true,
                'data' => new LoteResource($this->getLoteConGeometria($lote->id, ['proyectos.colaboradores'])),
                'message' => 'Lote experimental y proyectos creados con éxito.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o no proporcionado.',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'nombre_lote' => 'required|string|max:150',
        ]);

        try {
            $lote = $this->loteService->actualizarLote($id, $validated);

            return response()->json([
                'success' => true,
                'data' => new LoteResource($this->getLoteConGeometria($lote->id)),
                'message' => 'Lote actualizado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $this->loteService->eliminarLote($id);
            return response()->json([
                'success' => true,
                'message' => 'Lote eliminado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getLoteConGeometria(int $id, array $relations = []): Lote
    {
        return Lote::with($relations)
            ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
            ->findOrFail($id);
    }
}