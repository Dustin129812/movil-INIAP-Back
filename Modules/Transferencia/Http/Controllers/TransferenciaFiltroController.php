<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Transferencia\Services\TransferenciaFiltroService;

class TransferenciaFiltroController extends Controller
{
    public function __construct(
        private readonly TransferenciaFiltroService $filtroService
    ) {}

    /**
     * Obtener provincias con actividad en transferencia.
     */
    public function provincias(): JsonResponse
    {
        $provincias = $this->filtroService->getProvinciasActivas();

        return response()->json($provincias);
    }

    /**
     * Obtener cantones con actividad según la provincia.
     */
    public function cantones(int $provinciaId): JsonResponse
    {
        $cantones = $this->filtroService->getCantonesActivos($provinciaId);

        return response()->json($cantones);
    }

    /**
     * Obtener parroquias con actividad según el cantón.
     */
    public function parroquias(int $cantonId): JsonResponse
    {
        $parroquias = $this->filtroService->getParroquiasActivas($cantonId);

        return response()->json($parroquias);
    }
}
