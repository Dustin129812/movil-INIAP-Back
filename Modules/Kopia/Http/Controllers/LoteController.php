<?php

namespace Modules\Kopia\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Kopia\Entities\Lote;
use Modules\Kopia\Http\Requests\StoreLoteProyectoRequest;
use Modules\Kopia\Services\LoteService;
use Modules\Kopia\Transformers\LoteResource;

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
            $lote = $this->loteService->crearLoteIntegrado(
                $request->validated(),
                auth('api')->id()
            );

            $loteRefrescado = Lote::with(['proyectos.variedad.cultivo', 'proyectos.colaboradores'])
                ->select('*', DB::raw('ST_AsGeoJSON(area) as geometria_geojson'))
                ->find($lote->id);

            return response()->json([
                'data'    => new LoteResource($loteRefrescado),
                'message' => 'Lote experimental y proyectos creados de forma integrada con éxito.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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
