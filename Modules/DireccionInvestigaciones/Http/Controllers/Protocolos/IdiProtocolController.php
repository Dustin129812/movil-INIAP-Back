<?php

namespace Modules\DireccionInvestigaciones\Http\Controllers\Protocolos;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\DireccionInvestigaciones\Entities\Protocolos\IdiProtocol;
use Modules\DireccionInvestigaciones\Entities\Protocolos\ProtocolAnnex;
use Modules\DireccionInvestigaciones\Http\Requests\Protocolos\StoreIdiProtocolRequest;
use Modules\DireccionInvestigaciones\Services\Protocolos\IdiProtocolService;
use Modules\DireccionInvestigaciones\Transformers\Protocolos\IdiProtocolResource;

class IdiProtocolController extends Controller
{
    public function __construct(
        private readonly IdiProtocolService $protocolService
    ) {}

    /**
     * GET /api/direccion-investigaciones/protocolos
     */
    public function index(): JsonResponse
    {
        $protocols = IdiProtocol::with(['responsible', 'station'])->latest()->paginate(10);

        return response()->json([
            'data' => IdiProtocolResource::collection($protocols->items()),
            'meta' => [
                'current_page' => $protocols->currentPage(),
                'last_page'    => $protocols->lastPage(),
                'total'        => $protocols->total(),
            ]
        ]);
    }

    /**
     * POST /api/direccion-investigaciones/protocolos
     */
    public function store(StoreIdiProtocolRequest $request): JsonResponse
    {
        $protocol = $this->protocolService->createProtocol(
            $request->except(['annexes', 'canton_ids', 'collaborator_ids']),
            $request->input('collaborator_ids', []),
            $request->input('canton_ids', []),
            $request->file('annexes', [])
        );

        return response()->json([
            'message' => 'Protocolo creado exitosamente',
            'data'    => new IdiProtocolResource($protocol)
        ], 201);
    }

    /**
     * GET /api/direccion-investigaciones/protocolos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $protocol = IdiProtocol::with([
            'responsible',
            'station',
            'researchLine.area',
            'crop',
            'collaborators',
            'influenceCantons',
            'annexes'
        ])->findOrFail($id);

        return response()->json([
            'data' => new IdiProtocolResource($protocol)
        ]);
    }

    /**
     * GET /api/direccion-investigaciones/protocolos/download/{annexId}
     */
    public function downloadAnnex(int $annexId): StreamedResponse|JsonResponse
    {
        $annex = ProtocolAnnex::findOrFail($annexId);

        if (!Storage::disk('private')->exists($annex->file_path)) {
            return response()->json(['message' => 'Archivo no encontrado en el servidor'], 404);
        }

        return Storage::disk('private')->download($annex->file_path, $annex->file_name);
    }
}
