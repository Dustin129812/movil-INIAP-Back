<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Transferencia\Services\TransferenciaFiltroService;

class TransferenciaFiltroController extends Controller
{
    public function __construct(
        private readonly TransferenciaFiltroService $filtroService
    ) {}

    /**
     * Obtener provincias con actividad en transferencia.
     */
    public function provincias(Request $request): JsonResponse
    {
        $provincias = $this->filtroService->getProvinciasActivas($request->user()->location_id);

        return response()->json($provincias);
    }

    /**
     * Obtener cantones con actividad según la provincia.
     */
    public function cantones(Request $request, int $provinciaId): JsonResponse
    {
        $cantones = $this->filtroService->getCantonesActivos($provinciaId, $request->user()->location_id);

        return response()->json($cantones);
    }

    /**
     * Obtener parroquias con actividad según el cantón.
     */
    public function parroquias(Request $request, int $cantonId): JsonResponse
    {
        $parroquias = $this->filtroService->getParroquiasActivas($cantonId, $request->user()->location_id);

        return response()->json($parroquias);
    }

    public function estaciones(): JsonResponse
    {
        $estaciones = $this->filtroService->getEstaciones();
        return response()->json($estaciones);
    }
}
