<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Http\Requests\EnsayoRequest;
use Modules\Transferencia\Services\EnsayoService;
use Modules\Transferencia\Transformers\EnsayoResource;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnsayoController extends Controller
{
    public function __construct(
        private readonly EnsayoService $ensayoService
    ) {}

    public function index(EnsayoRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $filters['user_id'] = $request->user()->id;
        $filters['can_see_all'] = $request->user()->hasPermissionTo('transferencia.seguimiento_general');

        $ensayos = $this->ensayoService->paginate($filters);

        return EnsayoResource::collection($ensayos);
    }

    public function store(EnsayoRequest $request): EnsayoResource
    {
        $ensayo = $this->ensayoService->create($request->validated());
        return new EnsayoResource($ensayo);
    }

    public function show(EnsayoRequest $request, Ensayo $ensayo): EnsayoResource
    {
        $ensayo->load(['equipoTecnico'])->loadCount('parcelas');
        return new EnsayoResource($ensayo);
    }

    public function update(EnsayoRequest $request, Ensayo $ensayo): EnsayoResource
    {
        $ensayo = $this->ensayoService->update($ensayo, $request->validated());
        return new EnsayoResource($ensayo);
    }

    public function destroy(EnsayoRequest $request, Ensayo $ensayo): \Illuminate\Http\JsonResponse
    {
        $this->ensayoService->delete($ensayo);
        return response()->json(['message' => 'Ensayo eliminado correctamente']);
    }

    /**
     * Descarga el archivo de protocolo validando la firma temporal.
     */
    public function download(Ensayo $ensayo): StreamedResponse
    {
        return $this->ensayoService->downloadProtocolo($ensayo);
    }
}
