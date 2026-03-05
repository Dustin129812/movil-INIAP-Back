<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Transferencia\Entities\Parcela;
use Modules\Transferencia\Http\Requests\ParcelaRequest;
use Modules\Transferencia\Services\ParcelaService;
use Modules\Transferencia\Transformers\ParcelaResource;

class ParcelaController extends Controller
{
    public function __construct(
        private readonly ParcelaService $parcelaService
    ) {}

    public function index(ParcelaRequest $request): AnonymousResourceCollection
    {
        $parcelas = $this->parcelaService->paginate($request->validated());

        return ParcelaResource::collection($parcelas);
    }

    public function store(ParcelaRequest $request): ParcelaResource
    {
        $parcela = $this->parcelaService->create($request->validated());

        return new ParcelaResource($parcela);
    }

    public function show(ParcelaRequest $request, Parcela $parcela): ParcelaResource
    {
        $parcela->load(['ensayo', 'organizacion', 'provincia', 'canton', 'acuerdo']);

        return new ParcelaResource($parcela);
    }

    public function update(ParcelaRequest $request, Parcela $parcela): ParcelaResource
    {
        $parcela = $this->parcelaService->update($parcela, $request->validated());

        return new ParcelaResource($parcela);
    }

    public function destroy(ParcelaRequest $request, Parcela $parcela): JsonResponse
    {
        $this->parcelaService->delete($parcela);

        return response()->json(['message' => 'Parcela eliminada correctamente']);
    }
}
