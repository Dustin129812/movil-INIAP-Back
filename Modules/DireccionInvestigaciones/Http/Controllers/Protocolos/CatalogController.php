<?php

namespace Modules\DireccionInvestigaciones\Http\Controllers\Protocolos;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DireccionInvestigaciones\Services\Protocolos\CatalogService;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService
    ) {}

    /**
     * GET /api/direccion-investigaciones/protocolos/catalogs/all
     */
    public function index(): JsonResponse
    {
        try {
            $data = $this->catalogService->getAllCatalogs();

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar catálogos: ' . $e->getMessage()], 500);
        }
    }
}
