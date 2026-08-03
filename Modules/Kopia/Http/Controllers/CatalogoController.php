<?php

namespace Modules\Kopia\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;
use Modules\Kopia\Entities\Cultivo;
use Modules\Kopia\Entities\Variedad;
use Modules\Kopia\Http\Requests\StoreCultivoRequest;
use Modules\Kopia\Http\Requests\StoreVariedadRequest;
use Modules\Kopia\Services\CatalogoService;
use Modules\Kopia\Transformers\CultivoResource;
use Modules\Kopia\Transformers\VariedadResource;

class CatalogoController extends Controller
{
    public function __construct(
        private readonly CatalogoService $catalogoService
    ) {}

    public function index(): JsonResponse
    {
        $cultivos = $this->catalogoService->obtenerCatalogosCompletos();

        return response()->json([
            'success' => true,
            'data' => CultivoResource::collection($cultivos)
        ]);
    }

    public function storeCultivo(StoreCultivoRequest $request): JsonResponse
    {
        $cultivo = $this->catalogoService->crearCultivo($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cultivo registrado exitosamente.',
            'data' => new CultivoResource($cultivo)
        ], 201);
    }

    public function storeVariedad(StoreVariedadRequest $request): JsonResponse
    {
        $variedad = $this->catalogoService->crearVariedad($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Variedad registrada exitosamente.',
            'data' => new VariedadResource($variedad)
        ], 201);
    }

    public function syncCatalogosMobile(): JsonResponse
    {
        $provincias = Province::select('id', 'name')->get();
        $cantones = Canton::select('id', 'provincia_id', 'name')->get();
        $estaciones = Location::select('id', 'province_id', 'canton_id', 'name')->get();
        $cultivos = Cultivo::select('id', 'nombre', 'nombre_cientifico')->get();
        $variedades = Variedad::select('id', 'cultivo_id', 'nombre', 'caracteristicas_base')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'provincias' => $provincias,
                'cantones' => $cantones,
                'estaciones' => $estaciones,
                'cultivos' => $cultivos,
                'variedades' => $variedades,
            ]
        ], 200);
    }
}
