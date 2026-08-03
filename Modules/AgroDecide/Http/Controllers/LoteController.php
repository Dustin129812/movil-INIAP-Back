<?php

namespace Modules\AgroDecide\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\AgroDecide\Entities\Lote;
use Modules\AgroDecide\Http\Requests\StoreLoteProyectoRequest;
use Modules\AgroDecide\Transformers\LoteResource;

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
            // Extraer info del token directamente del payload JWT
            $payload = JWTAuth::parseToken()->getPayload();
            $isGuest = $payload->get('role') === 'guest';

            $responsableId = null;
            $dispositivoInvitadoId = null;

            if ($isGuest) {
                // Para invitados, usar el device_uuid del payload
                $dispositivoInvitadoId = $payload->get('device_uuid');
            } else {
                // Para usuarios normales, usar el sub del token (que es el user ID numérico)
                $sub = $payload->get('sub');
                $responsableId = is_numeric($sub) ? (int) $sub : null;
            }

            $lote = $this->loteService->crearLoteIntegrado(
                $request->validated(),
                $responsableId,
                $dispositivoInvitadoId
            );

            $loteRefrescado = Lote::with(['proyectos.colaboradores'])
                ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
                ->find($lote->id);

            return response()->json([
                'data'    => new LoteResource($loteRefrescado),
                'message' => 'Lote experimental y proyectos creados de forma integrada con éxito.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre_lote' => 'sometimes|string|max:150',
            'estado_verificacion' => 'sometimes|string|in:pendiente,verificado,rechazado',
        ]);

        try {
            $lote = $this->loteService->actualizarLote($id, $validated);

            $loteRefrescado = Lote::with(['proyectos'])
                ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
                ->find($lote->id);

            return response()->json([
                'data' => new LoteResource($loteRefrescado),
                'message' => 'Lote actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $this->loteService->eliminarLote($id);
            return response()->json(['message' => 'Lote eliminado exitosamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
