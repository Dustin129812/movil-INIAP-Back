<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    public function exportPlanificacion(Request $request)
    {
        // 1. OBTENER DATOS Y NORMALIZAR
        $response = app(PlannerController::class)->getProductsWithActivities($request);

        // --- NORMALIZACIÓN PROFUNDA ---
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $dataRaw = $response->getData(true);
        } elseif ($response instanceof \Illuminate\Contracts\Support\Arrayable) {
            $dataRaw = $response->toArray();
        } else {
            $dataRaw = (array) $response;
        }

        $productsSource = $dataRaw['data']['products']
            ?? $dataRaw['products']
            ?? $dataRaw['data']
            ?? $dataRaw;

        // Convertimos todo a array puro
        $products = json_decode(json_encode($productsSource), true);

        if (!is_array($products)) {
            $products = [];
        }

        // 2. DEFINIR PRIORIDADES DE FUENTE
        $prioridadFuentes = [
            'INVERSIÓN EXTERNA' => 1,
            'INVERSION EXTERNA' => 1,
            'FIASA'             => 2,
            'GASTO CORRIENTE'   => 3
        ];

        // 3. ORDENAR LA COLECCIÓN
        $products = collect($products)->sort(function ($a, $b) use ($prioridadFuentes) {
            // Criterio 1: Rubro ID
            $rubroA = $a['rubro']['id'] ?? 0;
            $rubroB = $b['rubro']['id'] ?? 0;

            $rubroCompare = $rubroA <=> $rubroB;
            if ($rubroCompare !== 0) {
                return $rubroCompare;
            }

            // Criterio 2: Fuente
            $rawA = $a['fund_source'] ?? $a['budget_type'] ?? '';
            $rawB = $b['fund_source'] ?? $b['budget_type'] ?? '';

            $valA = is_array($rawA) ? ($rawA['name'] ?? '') : $rawA;
            $valB = is_array($rawB) ? ($rawB['name'] ?? '') : $rawB;

            $fuenteA = mb_strtoupper(trim((string)$valA));
            $fuenteB = mb_strtoupper(trim((string)$valB));

            $pesoA = $prioridadFuentes[$fuenteA] ?? 99;
            $pesoB = $prioridadFuentes[$fuenteB] ?? 99;

            return $pesoA <=> $pesoB;
        })->values()->all();

        // 4. CALCULAR TOTALES POR RUBRO
        $totalesPorRubro = collect($products)->groupBy(function ($item) {
            return $item['rubro']['id'] ?? 0;
        })->map(function ($items) {
            return $items->sum('budget');
        });

        // 5. INICIAR EXCEL
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- LÓGICA DE ETIQUETAS DINÁMICAS (SINGLE LOCATION) ---
        $userLocationName = auth()->user()->location['name'] ?? 'Sistema';
        $isAdmCentral = ($userLocationName === 'ADM. CENTRAL');

        // Definimos los textos basados en la ubicación
        $labelRubro     = $isAdmCentral ? "Dirección/ Unidad: "      : "Rubro: ";
        $labelProducto  = $isAdmCentral ? "Gestión: "       : "Producto: ";
        $labelActividad = $isAdmCentral ? "Entregable: "    : "Actividad: ";
        // -------------------------------------------------------

        // --- ENCABEZADOS ---
        $sheet->setCellValue('A1', 'Reporte de Planificacion POA');
        $sheet->mergeCells('A1:AI1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
        ]);

        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->mergeCells('A2:AI2');

        $sheet->setCellValue('A3', 'Locación: ' . $userLocationName);
        $sheet->mergeCells('A3:AI3');

        $sheet->setCellValue('A4', 'Generado por: ' . (auth()->user()->name ?? 'Sistema'));
        $sheet->mergeCells('A4:AI4');

        // Tabla Header
        $headers = [
            $isAdmCentral ? "Entregable" : "Producto / Actividad",
            "Descripción", "Responsable", "Indicadores",
            "Presupuesto", "Fuente Financiamiento", "Presupuesto Ejecutado",
            "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
            "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
            "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
            "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
            "Observaciones"
        ];
        $sheet->fromArray($headers, null, 'A8');

        $sheet->getStyle('A8:AF8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'F2F3F2']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $row = 9;
        $lastRubroId = null;

        foreach ($products as $product) {
            $rubro = $product['rubro'] ?? [];
            $currentRubroId = $rubro['id'] ?? 0;

            // --- A. RUBRO (O ESTATUTO) ---
            if ($currentRubroId !== $lastRubroId) {
                // Usamos la variable $labelRubro
                $sheet->setCellValue("A{$row}", $labelRubro . ($rubro['name'] ?? 'Sin Clasificación'));
                $sheet->mergeCells("A{$row}:D{$row}");

                $totalRubro = $totalesPorRubro[$currentRubroId] ?? 0;
                $sheet->setCellValue("E{$row}", $totalRubro);

                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']],
                    'alignment' => ['vertical' => 'center']
                ]);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

                $row++;
                $lastRubroId = $currentRubroId;
            }

            // --- B. PRODUCTO (O GESTIÓN) ---
            // Usamos la variable $labelProducto
            $sheet->setCellValue("A{$row}", $labelProducto . ($product['name'] ?? ''));
            $sheet->setCellValue("E{$row}", $product['budget'] ?? 0);

            $rawSource = $product['fund_source'] ?? $product['budget_type'] ?? '';
            $sourceText = is_array($rawSource) ? ($rawSource['name'] ?? '') : $rawSource;

            $sheet->setCellValue("F{$row}", $sourceText);

            $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1F497D']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => 'center']
            ]);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

            $row++;

            // --- C. ACTIVIDADES (O ENTREGABLES) ---
            $activities = $product['activities'] ?? [];

            if (!empty($activities) && is_array($activities)) {
                foreach ($activities as $activity) {
                    if (!is_array($activity)) { continue; }

                    $usersList = $activity['users'] ?? [];
                    $responsables = implode(", ", array_column($usersList, 'name'));

                    $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                    $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                    // Planificación
                    $plan = array_fill(0, 12, "");
                    $planners = (array) ($activity['planners'] ?? $activity['monthly_plannig'] ?? []);
                    foreach ($planners as $p) {
                        if (isset($p['month'])) {
                            try {
                                $m = Carbon::parse($p['month'])->month - 1;
                                $plan[$m] = $p['planning'] ?? ($p['percentage'] ?? 0);
                            } catch (\Exception $e) {}
                        }
                    }

                    // Avance
                    $avance = array_fill(0, 12, "");
                    $progress = $activity['execution_progress'] ?? [];
                    if (is_array($progress)) {
                        foreach ($progress as $e) {
                            if (isset($e['month'])) {
                                try {
                                    $m = Carbon::parse($e['month'])->month - 1;
                                    $avance[$m] = ($e['reported_percentage'] ?? 0) . "%";
                                } catch (\Exception $e) {}
                            }
                        }
                    }

                    // Usamos la variable $labelActividad
                    $rowData = array_merge([
                        $labelActividad, // <-- AQUÍ CAMBIA EL PREFIJO
                        $activity['description'] ?? '',
                        $responsables,
                        $indicadores,
                        $activity['budget'] ?? 0,
                        "",
                        $activity['accrued_budget'] ?? 0,
                    ], $plan, $avance, [""]);

                    $sheet->fromArray($rowData, null, "A{$row}");
                    $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => 'center', 'wrapText' => true]
                    ]);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                    $row++;
                }
            }
        }

        // Finales
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);

        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 8; $col <= $highestColumnIndex; $col++) {
            $colString = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colString)->setWidth(12);
        }

        $lastRow = max($row - 1, 8);
        $sheet->getStyle("A6:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = "reporte_productos_actividades.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportPlanificacionAllLocations(Request $request)
    {
        $response = app(PlannerController::class)->getAllProductsWithActivities($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $dataRaw = $response->getData(true);
        } elseif ($response instanceof \Illuminate\Contracts\Support\Arrayable) {
            $dataRaw = $response->toArray();
        } else {
            $dataRaw = (array) $response;
        }

        $productsRaw = $dataRaw['data']['products']
            ?? $dataRaw['products']
            ?? $dataRaw['data']
            ?? $dataRaw;

        $productsRaw = json_decode(json_encode($productsRaw), true);

        if (!is_array($productsRaw)) {
            $productsRaw = [];
        }

        $prioridadFuentes = [
            'INVERSIÓN EXTERNA' => 1,
            'INVERSION EXTERNA' => 1,
            'FIASA'             => 2,
            'GASTO CORRIENTE'   => 3
        ];

        $groupedProducts = collect($productsRaw)->groupBy(function ($item) {
            return $item['location']['name'] ?? 'Sin Locación';
        });

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($groupedProducts as $locationName => $productsList) {

            // --- LÓGICA DE ETIQUETAS DINÁMICAS (MULTI LOCATION) ---
            // Aquí determinamos etiquetas POR CADA HOJA según el nombre de la locación
            $isAdmCentral = ($locationName === 'ADM. CENTRAL');

            $labelRubro     = $isAdmCentral ? "Dirección/Unidad: "      : "Rubro: ";
            $labelProducto  = $isAdmCentral ? "Gestión: "       : "Producto: ";
            $labelActividad = $isAdmCentral ? "Entregable: "    : "Actividad: ";
            // ------------------------------------------------------

            $products = collect($productsList)->sort(function ($a, $b) use ($prioridadFuentes) {
                $rubroA = (isset($a['rubro']) && is_array($a['rubro'])) ? ($a['rubro']['id'] ?? 0) : 0;
                $rubroB = (isset($b['rubro']) && is_array($b['rubro'])) ? ($b['rubro']['id'] ?? 0) : 0;

                $rubroCompare = $rubroA <=> $rubroB;
                if ($rubroCompare !== 0) {
                    return $rubroCompare;
                }

                $valA = $a['fund_source'] ?? $a['budget_type'] ?? '';
                $valB = $b['fund_source'] ?? $b['budget_type'] ?? '';

                $fuenteA = mb_strtoupper(trim($valA));
                $fuenteB = mb_strtoupper(trim($valB));

                $pesoA = $prioridadFuentes[$fuenteA] ?? 99;
                $pesoB = $prioridadFuentes[$fuenteB] ?? 99;

                return $pesoA <=> $pesoB;
            })->values()->all();

            $totalesPorRubro = collect($products)->groupBy(function($item){
                return $item['rubro']['id'] ?? 0;
            })->map(function ($items) {
                return $items->sum('budget');
            });

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($locationName, 0, 31));

            $sheet->setCellValue('A1', 'Reporte de Planificación POA - ' . $locationName);
            $sheet->mergeCells('A1:AI1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
            ]);

            $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
            $sheet->mergeCells('A2:AI2');
            $sheet->setCellValue('A3', 'Locación: ' . $locationName);
            $sheet->mergeCells('A3:AI3');
            $sheet->setCellValue('A4', 'Generado por: ' . (auth()->user()->name ?? 'Sistema'));
            $sheet->mergeCells('A4:AI4');

            $headers = [
                $isAdmCentral ? "Gestión / Entregable" : "Producto / Actividad", // Header dinámico
                "Descripción", "Responsable", "Indicadores",
                "Presupuesto", "Fuente Financiamiento", "Presupuesto Ejecutado",
                "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
                "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
                "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
                "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
                "Observaciones"
            ];
            $sheet->fromArray($headers, null, 'A8');

            $sheet->getStyle('A8:AF8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'F2F3F2']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $row = 9;
            $lastRubroId = null;

            foreach ($products as $product) {
                $rubro = $product['rubro'] ?? [];
                $currentRubroId = $rubro['id'] ?? 0;

                if ($currentRubroId !== $lastRubroId) {
                    // Usamos variable $labelRubro
                    $sheet->setCellValue("A{$row}", $labelRubro . ($rubro['name'] ?? 'Sin Rubro'));
                    $sheet->mergeCells("A{$row}:D{$row}");
                    $sheet->setCellValue("E{$row}", $totalesPorRubro[$currentRubroId] ?? 0);

                    $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']],
                        'alignment' => ['vertical' => 'center']
                    ]);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                    $row++;
                    $lastRubroId = $currentRubroId;
                }

                // Usamos variable $labelProducto
                $sheet->setCellValue("A{$row}", $labelProducto . ($product['name'] ?? ''));
                $sheet->setCellValue("E{$row}", $product['budget'] ?? 0);
                $sheet->setCellValue("F{$row}", $product['fund_source'] ?? $product['budget_type'] ?? '');

                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F497D']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => 'center']
                ]);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                $row++;

                $activities = $product['activities'] ?? [];
                if (!empty($activities) && is_array($activities)) {
                    foreach ($activities as $activity) {

                        if (!is_array($activity)) { continue; }

                        $usersList = $activity['users'] ?? [];
                        $responsables = implode(", ", array_column($usersList, 'name'));

                        $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                        $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                        $plan = array_fill(0, 12, "");
                        $planners = (array) ($activity['planners'] ?? $activity['monthly_plannig'] ?? []);
                        foreach ($planners as $p) {
                            if (isset($p['month'])) {
                                try {
                                    $m = Carbon::parse($p['month'])->month - 1;
                                    $plan[$m] = $p['planning'] ?? ($p['percentage'] ?? 0);
                                } catch (\Exception $e) {}
                            }
                        }

                        $avance = array_fill(0, 12, "");
                        $progress = $activity['execution_progress'] ?? [];
                        if(is_array($progress)){
                            foreach ($progress as $e) {
                                if (isset($e['month'])) {
                                    try {
                                        $m = Carbon::parse($e['month'])->month - 1;
                                        $avance[$m] = ($e['reported_percentage'] ?? 0) . "%";
                                    } catch (\Exception $e) {}
                                }
                            }
                        }

                        // Usamos variable $labelActividad
                        $rowData = array_merge([
                            $labelActividad, // <-- CAMBIO AQUÍ
                            $activity['description'] ?? '',
                            $responsables,
                            $indicadores,
                            $activity['budget'] ?? 0,
                            "",
                            $activity['accrued_budget'] ?? 0
                        ], $plan, $avance, [""]);

                        $sheet->fromArray($rowData, null, "A{$row}");
                        $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['vertical' => 'center', 'wrapText' => true]
                        ]);
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                        $row++;
                    }
                }
            }

            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(50);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(15);
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            for ($col = 8; $col <= $highestColumnIndex; $col++) {
                $colString = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colString)->setWidth(12);
            }
            $lastRow = max($row - 1, 8);
            $sheet->getStyle("A8:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "reporte_consolidado_locaciones.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
