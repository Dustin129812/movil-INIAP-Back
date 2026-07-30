<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;
use Modules\AgroDecide\Entities\Cultivo;
use Modules\AgroDecide\Entities\Variedad;
use Modules\AgroDecide\Http\Requests\StoreCultivoRequest;
use Modules\AgroDecide\Http\Requests\StoreVariedadRequest;
use Modules\AgroDecide\Services\CatalogoService;
use Modules\AgroDecide\Transformers\CultivoResource;
use Modules\AgroDecide\Transformers\VariedadResource;

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
