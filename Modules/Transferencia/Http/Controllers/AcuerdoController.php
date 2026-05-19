<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Transferencia\Http\Requests\AcuerdoRequest;
use Modules\Transferencia\Services\AcuerdoService;
use Modules\Transferencia\Transformers\AcuerdoResource;
use Modules\Transferencia\Entities\Acuerdo;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcuerdoController extends Controller
{
    public function __construct(
        private readonly AcuerdoService $acuerdoService
    ) {}

    public function index(AcuerdoRequest $request): AnonymousResourceCollection
    {
        $acuerdos = $this->acuerdoService->paginate($request->validated());

        return AcuerdoResource::collection($acuerdos);
    }

    public function store(AcuerdoRequest $request): AcuerdoResource
    {
        $acuerdo = $this->acuerdoService->create($request->validated());

        return new AcuerdoResource($acuerdo);
    }

    public function show(AcuerdoRequest $request, Acuerdo $acuerdo): AcuerdoResource
    {
        $acuerdo->load('organizacion');
        return new AcuerdoResource($acuerdo);
    }

    public function update(AcuerdoRequest $request, Acuerdo $acuerdo): AcuerdoResource
    {
        $acuerdoActualizado = $this->acuerdoService->update($acuerdo, $request->validated());

        return new AcuerdoResource($acuerdoActualizado);
    }

    public function destroy(AcuerdoRequest $request, Acuerdo $acuerdo): JsonResponse
    {
        $this->acuerdoService->delete($acuerdo);

        return response()->json(['message' => 'Acuerdo eliminado correctamente']);
    }

    /**
     * Descarga el archivo de acuerdo validando la firma temporal.
     */
    public function download(Acuerdo $acuerdo): StreamedResponse
    {
        return $this->acuerdoService->downloadFile($acuerdo);
    }
}
