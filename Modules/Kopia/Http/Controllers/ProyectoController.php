<?php

namespace Modules\Kopia\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Kopia\Http\Requests\StoreProyectoRequest;
use Modules\Kopia\Services\ProyectoService;
use Modules\Kopia\Transformers\ProyectoResource;

class ProyectoController extends Controller
{
    public function __construct(
        private readonly ProyectoService $proyectoService
    ) {}

    public function index(): JsonResponse
    {
        $proyectos = $this->proyectoService->listarParaUsuario(auth('api')->id());

        return response()->json([
            'success' => true,
            'data' => ProyectoResource::collection($proyectos)
        ]);
    }

    public function store(StoreProyectoRequest $request): JsonResponse
    {
        $proyecto = $this->proyectoService->crearProyecto(
            $request->validated(),
            auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Proyecto creado exitosamente.',
            'data' => new ProyectoResource($proyecto)
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $proyecto = $this->proyectoService->obtenerDetalleCompleto($id);

        return response()->json([
            'success' => true,
            'data' => new ProyectoResource($proyecto)
        ]);
    }
}
