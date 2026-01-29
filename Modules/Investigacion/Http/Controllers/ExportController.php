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
        // 1. OBTENER DATOS
        $productsRaw = app(PlannerController::class)->getProductsWithActivities($request);

        if ($productsRaw instanceof \Illuminate\Http\JsonResponse) {
            $products = $productsRaw->getData(true)['data']['products'] ?? [];
        } else {
            // If it's a Collection or Array, convert to array
            $data = $productsRaw instanceof \Illuminate\Support\Collection ? $productsRaw->toArray() : $productsRaw;
            $products = $data['products'] ?? $data;
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
            // Criterio 1: Rubro ID (Para que no se rompan los bloques de rubros)
            $rubroA = $a['rubro']['id'] ?? 0;
            $rubroB = $b['rubro']['id'] ?? 0;

            $rubroCompare = $rubroA <=> $rubroB;
            if ($rubroCompare !== 0) {
                return $rubroCompare;
            }

            // Criterio 2: Fuente de Financiamiento (CORREGIDO)
            // Buscamos en fund_source Y en budget_type, igual que en el Excel
            $valA = $a['fund_source'] ?? $a['budget_type'] ?? '';
            $valB = $b['fund_source'] ?? $b['budget_type'] ?? '';

            $fuenteA = mb_strtoupper(trim($valA));
            $fuenteB = mb_strtoupper(trim($valB));

            // Asignamos el peso
            $pesoA = $prioridadFuentes[$fuenteA] ?? 99;
            $pesoB = $prioridadFuentes[$fuenteB] ?? 99;

            return $pesoA <=> $pesoB;
        })->values()->all();

        // 4. CALCULAR TOTALES POR RUBRO (Pre-cálculo)
        $totalesPorRubro = collect($products)->groupBy('rubro.id')->map(function ($items) {
            return $items->sum('budget');
        });

        // 5. INICIAR EXCEL
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- ENCABEZADOS DEL DOCUMENTO ---

        // Título
        $sheet->setCellValue('A1', 'Reporte de Planificacion POA');
        $sheet->mergeCells('A1:AI1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
        ]);

        // Fecha
        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->mergeCells('A2:AI2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);

        // Locación
        $sheet->setCellValue('A3', 'Locación: ' . (auth()->user()->location['name'] ?? 'Sistema'));
        $sheet->mergeCells('A3:AI3');
        $sheet->getStyle('A3')->applyFromArray(['alignment' => ['horizontal' => 'left']]);

        // Generado por
        $sheet->setCellValue('A4', 'Generado por: ' . (auth()->user()->name ?? 'Sistema'));
        $sheet->mergeCells('A4:AI4');
        $sheet->getStyle('A4')->applyFromArray(['alignment' => ['horizontal' => 'left']]);

        $sheet->setCellValue('A5', 'Objetivo: ');
        $sheet->mergeCells('A5:AI5');

        $sheet->setCellValue('A6', 'Director: ');
        $sheet->mergeCells('A6:AI6');

        // --- ENCABEZADOS DE TABLA (FILA 8) ---
        $headers = [
            "Producto / Actividad", "Descripción", "Responsable", "Indicadores",
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
            $currentRubroId = $product['rubro']['id'] ?? 0;

            // --- A. IMPRIMIR CABECERA DE RUBRO (Si cambió) ---
            if ($currentRubroId !== $lastRubroId) {

                // 1. Nombre del Rubro
                $sheet->setCellValue("A{$row}", "Rubro: " . ($product['rubro']['name'] ?? 'Sin Rubro'));
                // Combinamos solo A hasta D para dejar E libre para el total
                $sheet->mergeCells("A{$row}:D{$row}");

                // 2. Total del Rubro (Columna E)
                $totalRubro = $totalesPorRubro[$currentRubroId] ?? 0;
                $sheet->setCellValue("E{$row}", $totalRubro);

                // Estilos del Rubro
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']],
                    'alignment' => ['vertical' => 'center']
                ]);

                // Formato moneda para el total del rubro
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

                $row++;
                $lastRubroId = $currentRubroId;
            }

            // --- B. FILA DEL PRODUCTO ---

            $sheet->setCellValue("A{$row}", "Producto: " . $product['name']);
            $sheet->setCellValue("E{$row}", $product['budget']);
            $sheet->setCellValue("F{$row}", $product['fund_source'] ?? $product['budget_type'] ?? ''); // Asegurar campo correcto

            // Estilos producto
            $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1F497D'] // Azul oscuro (Arreglado el error setColor)
                ],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']], // Gris claro
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => 'center']
            ]);

            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

            $row++;

            // --- C. ACTIVIDADES DE ESTE PRODUCTO ---

            if (!empty($product['activities'])) {
                foreach ($product['activities'] as $activity) {

                    $usersList = $activity['users'] ?? [];
                    $responsables = implode(", ", array_column($usersList, 'name'));

                    $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                    $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                    // 1. MAPEO DE PLANIFICACIÓN (CORREGIDO)
                    $plan = array_fill(0, 12, "");
                    $planners = (array) ($activity['planners'] ?? $activity['monthly_plannig'] ?? []);

                    foreach ($planners as $p) {
                        if (is_array($p) && isset($p['month'])) {
                            try {
                                $m = Carbon::parse($p['month'])->month - 1;
                                $val = isset($p['planning']) ? $p['planning'] : ($p['percentage'] ?? 0);
                                $plan[$m] = $val;
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    // 2. MAPEO DE AVANCE (AQUÍ TAMBIÉN DABA ERROR)
                    $avance = array_fill(0, 12, "");
                    $progress = $activity['execution_progress'] ?? [];

                    if (is_array($progress)) {
                        foreach ($progress as $e) {
                            // Aplicamos la misma lógica de seguridad para el avance
                            if (is_array($e) && isset($e['month'])) {
                                $m = Carbon::parse($e['month'])->month - 1;
                                $avance[$m] = ($e['reported_percentage'] ?? 0) . "%";
                            }
                        }
                    }

                    $rowData = array_merge([
                        "Actividad: ",
                        $activity['description'] ?? '',
                        $responsables,
                        $indicadores,
                        $activity['budget'] ?? 0,
                        "",
                        $activity['accrued_budget'] ?? 0,
                    ], $plan, $avance, [""]);

                    $sheet->fromArray($rowData, null, "A{$row}");

                    // Estilos actividad
                    $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => 'center', 'wrapText' => true]
                    ]);

                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                    $row++;
                }
            }
        }

        // --- AJUSTES FINALES ---

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(18); // Presupuesto
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);

        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 8; $col <= $highestColumnIndex; $col++) {
            $colString = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colString)->setWidth(12);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A6:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = "reporte_productos_actividades.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportPlanificacionAllLocations(Request $request)
    {
        // 1. OBTENER DATOS
        $response = app(PlannerController::class)->getAllProductsWithActivities($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $productsRaw = $response->getData(true)['data']['products'] ?? [];
        } else {
            $data = $response instanceof \Illuminate\Support\Collection ? $response->toArray() : $response;
            $productsRaw = $data['products'] ?? $data;
        }

        // 2. DEFINIR PRIORIDADES (Igual que en la función individual)
        $prioridadFuentes = [
            'INVERSIÓN EXTERNA' => 1,
            'INVERSION EXTERNA' => 1,
            'FIASA'             => 2,
            'GASTO CORRIENTE'   => 3
        ];

        // 3. AGRUPAR POR LOCACIÓN
        $groupedProducts = collect($productsRaw)->groupBy(function ($item) {
            return $item['location']['name'] ?? 'Sin Locación';
        });

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Eliminamos la hoja en blanco inicial

        // 4. ITERAR POR CADA LOCACIÓN (CADA UNA ES UNA HOJA)
        foreach ($groupedProducts as $locationName => $productsList) {

            // --- A. APLICAR LÓGICA DE ORDENAMIENTO (Por cada locación) ---
            $products = collect($productsList)->sort(function ($a, $b) use ($prioridadFuentes) {
                // Criterio 1: Rubro ID
                $rubroA = $a['rubro']['id'] ?? 0;
                $rubroB = $b['rubro']['id'] ?? 0;

                $rubroCompare = $rubroA <=> $rubroB;
                if ($rubroCompare !== 0) {
                    return $rubroCompare;
                }

                // Criterio 2: Fuente de Financiamiento (Buscando en ambos campos)
                $valA = $a['fund_source'] ?? $a['budget_type'] ?? '';
                $valB = $b['fund_source'] ?? $b['budget_type'] ?? '';

                $fuenteA = mb_strtoupper(trim($valA));
                $fuenteB = mb_strtoupper(trim($valB));

                $pesoA = $prioridadFuentes[$fuenteA] ?? 99;
                $pesoB = $prioridadFuentes[$fuenteB] ?? 99;

                return $pesoA <=> $pesoB;
            })->values()->all();

            // --- B. CALCULAR TOTALES POR RUBRO (Por cada locación) ---
            $totalesPorRubro = collect($products)->groupBy('rubro.id')->map(function ($items) {
                return $items->sum('budget');
            });

            // Creación de la hoja
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($locationName, 0, 31)); // Límite de Excel 31 chars

            // Encabezados Generales
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

            // Encabezados de Tabla
            $headers = [
                "Producto / Actividad", "Descripción", "Responsable", "Indicadores",
                "Presupuesto", "Fuente Financiamiento", "Presupuesto Ejecutado",
                "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
                "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
                "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
                "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
                "Observaciones"
            ];
            $sheet->fromArray($headers, null, 'A8');

            // Estilos del header
            $sheet->getStyle('A8:AF8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'F2F3F2']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $row = 9;
            $lastRubroId = null;

            foreach ($products as $product) {
                $currentRubroId = $product['rubro']['id'] ?? 0;

                // --- C. IMPRIMIR RUBRO CON TOTAL ---
                if ($currentRubroId !== $lastRubroId) {
                    // Nombre
                    $sheet->setCellValue("A{$row}", "Rubro: " . ($product['rubro']['name'] ?? 'Sin Rubro'));
                    // Combinar solo A-D
                    $sheet->mergeCells("A{$row}:D{$row}");

                    // Total en columna E
                    $totalRubro = $totalesPorRubro[$currentRubroId] ?? 0;
                    $sheet->setCellValue("E{$row}", $totalRubro);

                    // Estilos Rubro
                    $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']],
                        'alignment' => ['vertical' => 'center']
                    ]);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

                    $row++;
                    $lastRubroId = $currentRubroId;
                }

                // --- D. FILA DE PRODUCTO ---
                $sheet->setCellValue("A{$row}", "Producto: " . $product['name']);
                $sheet->setCellValue("E{$row}", $product['budget']);
                // Usamos la misma lógica visual de fuente
                $sheet->setCellValue("F{$row}", $product['fund_source'] ?? $product['budget_type'] ?? '');

                // Estilos Producto (Azul)
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '1F497D'] // Azul
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']], // Gris claro
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => 'center']
                ]);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

                $row++;

                // --- E. ACTIVIDADES ---
                if (!empty($product['activities'])) {
                    foreach ($product['activities'] as $activity) {
                        $usersList = $activity['users'] ?? [];
                        $responsables = implode(", ", array_column($usersList, 'name'));

                        $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                        $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                        // Mapeo Planificación
                        $plan = array_fill(0, 12, "");
                        $planners = $activity['planners'] ?? $activity['monthly_plannig'] ?? [];

                        foreach ((array) $planners as $p) {
                            if (!is_array($p) || !isset($p['month'])) {
                                continue;
                            }
                            $m = Carbon::parse($p['month'])->month - 1;
                            $val = $p['planning'] ?? ($p['percentage'] ?? 0);
                            $plan[$m] = $val;
                        }

                        // Mapeo Avance
                        $avance = array_fill(0, 12, "");
                        $progress = $activity['execution_progress'] ?? [];
                        foreach ((array) $progress as $e) {
                            if (!is_array($e) || !isset($e['month'])) {
                                continue;
                            }
                            $m = Carbon::parse($e['month'])->month - 1;
                            $avance[$m] = ($e['reported_percentage'] ?? 0) . "%";
                        }

                        $rowData = array_merge([
                            "Actividad: ",
                            $activity['description'] ?? '',
                            $responsables,
                            $indicadores,
                            $activity['budget'] ?? 0,
                            "",
                            $activity['accrued_budget'] ?? 0
                        ], $plan, $avance, [""]);

                        $sheet->fromArray($rowData, null, "A{$row}");

                        // Estilos Actividad
                        $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['vertical' => 'center', 'wrapText' => true]
                        ]);
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

                        $row++;
                    }
                }
            }

            // Ajuste de anchos para esta hoja
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

            $lastRow = $row - 1;
            $sheet->getStyle("A8:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "reporte_consolidado_locaciones.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
