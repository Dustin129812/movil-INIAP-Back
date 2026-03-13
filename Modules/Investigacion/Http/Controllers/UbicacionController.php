<?php

namespace Modules\Investigacion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Investigacion\Services\UbicacionService;

class UbicacionController extends Controller
{
    public function __construct(private readonly UbicacionService $ubicacionService) {}

    public function getProvincias(): JsonResponse
    {
        return response()->json($this->ubicacionService->getAllProvincias());
    }

    public function getCantonesPorProvincia(int $provinciaId): JsonResponse
    {
        return response()->json($this->ubicacionService->getCantonesByProvincia($provinciaId));
    }

    public function getParroquiasPorCanton(int $cantonId): JsonResponse
    {
        return response()->json($this->ubicacionService->getParroquiasByCanton($cantonId));
    }
}
