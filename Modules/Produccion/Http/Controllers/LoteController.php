<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\Lote;
use Modules\Produccion\Http\Requests\Lote\SegmentarLoteRequest;
use Modules\Produccion\Http\Requests\Lote\UpdateLoteRequest;
use Modules\Produccion\Services\LoteService;
use Modules\Produccion\Traits\ApiResponse;
use Modules\Produccion\Transformers\LoteResource;

class LoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LoteService $loteService
    ) {}

    public function index(): JsonResponse
    {
        $lotes = Lote::whereNull('parent_id')
            ->with(['hijos' => function($query) {
                $query->select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'));
            }])
            ->select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'))
            ->get();

        $data = LoteResource::collection($lotes);

        return $this->successResponse($data);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'location_id' => 'required|integer',
            'nombre' => 'required|string|max:100',
            'superficie_hectareas' => 'required|numeric',
            'poligono_geojson' => 'required|string',
            'estado' => 'required|string'
        ]);

        $lote = $this->loteService->crearLote($v);

        $lote = Lote::select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'))
            ->find($lote->id);

        return $this->successResponse(
            new LoteResource($lote),
            'Lote georreferenciado creado con éxito.',
            201
        );
    }

    public function update(UpdateLoteRequest $request, $id)
    {
        try {
            $lote = $this->loteService->actualizarLote($id, $request->validated());

            // Refrescamos el lote para adjuntar su GeoJSON y sus hijos en la respuesta
            $loteRefrescado = Lote::select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'))
                ->with(['hijos' => function($query) {
                    $query->select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'));
                }])
                ->find($lote->id);

            return $this->successResponse(
                new LoteResource($loteRefrescado),
                'Lote actualizado correctamente.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            $this->loteService->eliminarLote($id);
            return $this->successResponse(null, 'Lote georreferenciado eliminado exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function segmentar(SegmentarLoteRequest $request, $parentId)
    {
        try {
            $subLote = $this->loteService->segmentarLote($parentId, $request->validated());

            $subLote = Lote::select('*', DB::raw('ST_AsGeoJSON(poligono) as poligono_geojson'))
                ->find($subLote->id);

            return $this->successResponse(new LoteResource($subLote), 'Segmento georreferenciado creado con éxito.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
