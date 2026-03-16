<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Transferencia\Http\Requests\ImportarDpaRequest;
use Modules\Transferencia\Services\DpaService;

class DpaController extends Controller
{
    public function __construct(
        private readonly DpaService $dpaService
    )
    {
    }

    public function importar(ImportarDpaRequest $request): JsonResponse
    {
        $estadisticas = $this->dpaService->importarDpaCsv($request->file('archivo_dpa'));

        return response()->json([
            'message' => 'División Político Administrativa importada correctamente.',
            'data' => $estadisticas
        ]);
    }
}
