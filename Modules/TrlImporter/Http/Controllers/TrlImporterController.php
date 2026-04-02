<?php

namespace Modules\TrlImporter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\TrlImporter\Http\Requests\ImportExcelRequest;
use Modules\TrlImporter\Services\ExcelImportService;

class TrlImporterController extends Controller
{
    protected ExcelImportService $importService;

    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
    }

    public function upload(ImportExcelRequest $request): JsonResponse
    {
        $resultado = $this->importService->processExcel($request->file('archivo_excel'));

        return response()->json($resultado);
    }
}
