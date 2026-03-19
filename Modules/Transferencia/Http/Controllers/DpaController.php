<?php

namespace Modules\Transferencia\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Transferencia\Http\Requests\ImportarDpaRequest;
use Modules\Transferencia\Services\DpaService;

class DpaController extends Controller
{
    public function __construct(
        private readonly DpaService $dpaService
    ) {}

    public function importar(ImportarDpaRequest $request): JsonResponse
    {
        $stats = $this->dpaService->importar($request->file('archivo_dpa'));

        return response()->json([
            'message' => 'Sincronización territorial completada con éxito.',
            'data' => $stats
        ]);
    }
}
