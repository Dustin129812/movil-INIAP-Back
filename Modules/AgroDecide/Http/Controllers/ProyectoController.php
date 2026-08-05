<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AgroDecide\Http\Requests\StoreProyectoRequest;
use Modules\AgroDecide\Services\ProyectoService;
use Modules\AgroDecide\Transformers\ProyectoResource;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProyectoController extends Controller
{
    public function __construct(
        private readonly ProyectoService $proyectoService
    ) {}

    public function index(): JsonResponse
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('sub');
        $proyectos = $this->proyectoService->listarParaUsuario($userId);

        return response()->json([
            'success' => true,
            'data' => ProyectoResource::collection($proyectos)
        ]);
    }

    public function store(StoreProyectoRequest $request): JsonResponse
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('sub');
        $proyecto = $this->proyectoService->crearProyecto(
            $request->validated(),
            $userId
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
