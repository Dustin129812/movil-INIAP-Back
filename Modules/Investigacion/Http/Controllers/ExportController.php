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
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /**
     * Función auxiliar para filtrar productos
     */
    private function applyFilters($products, Request $request)
    {
        return collect($products)->filter(function ($product) use ($request) {

            // 1. Filtro Global
            if ($search = $request->get('global')) {
                $search = mb_strtolower($search);
                $found = false;

                if (str_contains(mb_strtolower($product['name'] ?? ''), $search)) $found = true;
                if (str_contains(mb_strtolower($product['description'] ?? ''), $search)) $found = true;
                if (str_contains(mb_strtolower($product['rubro']['name'] ?? ''), $search)) $found = true;

                $activities = $product['activities'] ?? [];
                foreach ($activities as $act) {
                    if (str_contains(mb_strtolower($act['description'] ?? ''), $search)) $found = true;
                    $inds = $act['indicators'] ?? $act['performance_indicators'] ?? [];
                    foreach ($inds as $ind) {
                        if (str_contains(mb_strtolower($ind['name'] ?? ''), $search)) $found = true;
                    }
                }
                if (!$found) return false;
            }

            // 2. Filtro Año
            if ($year = $request->get('year')) {
                $pYear = isset($product['create_at']) ? substr($product['create_at'], 0, 4) : null;
                if (!$pYear && !empty($product['activities'][0]['start_date'])) {
                    $pYear = substr($product['activities'][0]['start_date'], 0, 4);
                }
                if ($pYear != $year) return false;
            }

            // 3. Filtro Rubro
            if ($rubro = $request->get('rubro')) {
                if (($product['rubro']['name'] ?? '') !== $rubro) return false;
            }

            // 4. Filtro Responsable
            if ($responsible = $request->get('responsible')) {
                $searchRes = mb_strtolower($responsible);
                $foundRes = false;
                $pUser = ($product['user']['name'] ?? '') . ' ' . ($product['user']['last_name'] ?? '');
                if (str_contains(mb_strtolower($pUser), $searchRes)) $foundRes = true;

                if (!$foundRes) {
                    $activities = $product['activities'] ?? [];
                    foreach ($activities as $act) {
                        $users = $act['users'] ?? [];
                        foreach ($users as $u) {
                            $uName = ($u['name'] ?? '') . ' ' . ($u['last_name'] ?? '');
                            if (str_contains(mb_strtolower($uName), $searchRes)) $foundRes = true;
                        }
                    }
                }
                if (!$foundRes) return false;
            }

            // 5. Filtro Tipo Presupuesto
            if ($budgetType = $request->get('budgetType')) {
                $pType = $product['budget_type']['name'] ?? $product['budget_type'] ?? '';
                if ($pType !== $budgetType) return false;
            }

            return true;
        })->values()->all();
    }

    public function exportPlanificacion(Request $request)
    {
        // 1. OBTENER DATOS
        $response = app(PlannerController::class)->getProductsWithActivities($request);

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

        $products = json_decode(json_encode($productsSource), true);

        if (!is_array($products)) {
            $products = [];
        }

        // 2. APLICAR FILTROS
        $products = $this->applyFilters($products, $request);

        // 3. DEFINIR PRIORIDADES
        $prioridadFuentes = [
            'INVERSIÓN EXTERNA' => 1, 'INVERSION EXTERNA' => 1,
            'FIASA' => 2,
            'GASTO CORRIENTE' => 3
        ];

        // 4. ORDENAR LA COLECCIÓN (AQUÍ ESTABA EL ERROR)
        $products = collect($products)->sort(function ($a, $b) use ($prioridadFuentes) {
            // Criterio 1: Rubro ID
            $rubroA = $a['rubro']['id'] ?? 0;
            $rubroB = $b['rubro']['id'] ?? 0;

            // CORRECCIÓN: Guardamos el resultado en una variable
            $cmpRubro = $rubroA <=> $rubroB;
            if ($cmpRubro !== 0) {
                return $cmpRubro;
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

        // 5. CALCULAR TOTALES
        $totalesPorRubro = collect($products)->groupBy(function ($item) {
            return $item['rubro']['id'] ?? 0;
        })->map(function ($items) {
            return $items->sum('budget');
        });

        // 6. EXCEL
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $userLocationName = auth()->user()->location['name'] ?? 'Sistema';
        $isAdmCentral = ($userLocationName === 'ADM. CENTRAL' || $userLocationName === 'ADM CENTRAL');

        $labelRubro     = $isAdmCentral ? "Dirección/Unidad: " : "Rubro: ";
        $labelProducto  = $isAdmCentral ? "Gestión: "          : "Producto: ";
        $labelActividad = $isAdmCentral ? "Entregable: "       : "Actividad: ";

        // HEADERS
        $sheet->setCellValue('A1', 'Reporte de Planificacion POA');
        $sheet->mergeCells('A1:AI1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']]);

        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->mergeCells('A2:AI2');
        $sheet->setCellValue('A3', 'Locación: ' . $userLocationName);
        $sheet->mergeCells('A3:AI3');
        $sheet->setCellValue('A4', 'Generado por: ' . (auth()->user()->name ?? 'Sistema'));
        $sheet->mergeCells('A4:AI4');

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

            if ($currentRubroId !== $lastRubroId) {
                $sheet->setCellValue("A{$row}", $labelRubro . ($rubro['name'] ?? 'Sin Clasificación'));
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

            $sheet->setCellValue("A{$row}", $labelProducto . ($product['name'] ?? ''));
            $sheet->setCellValue("E{$row}", $product['budget'] ?? 0);
            $rawSource = $product['fund_source'] ?? $product['budget_type'] ?? '';
            $sheet->setCellValue("F{$row}", is_array($rawSource) ? ($rawSource['name'] ?? '') : $rawSource);

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
                    if (!is_array($activity)) continue;

                    $usersList = $activity['users'] ?? [];
                    $responsables = implode(", ", array_column($usersList, 'name'));
                    $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                    $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                    // --- CORRECCIÓN PLANIFICACIÓN ---
                    $plan = array_fill(0, 12, "");

                    // Agregamos 'monthly_planning' (con n) y 'monthly_progress' para cubrir ambos casos
                    $planners = (array) ($activity['planners']
                        ?? $activity['monthly_planning']
                        ?? $activity['monthly_progress']
                        ?? []);

                    foreach ($planners as $p) {
                        if (isset($p['month'])) {
                            try {
                                $m = Carbon::parse($p['month'])->month - 1;
                                // Buscamos 'planning' O 'percentage'
                                $val = $p['planning'] ?? $p['percentage'] ?? 0;
                                $plan[$m] = (is_numeric($val) && $val > 0) ? ($val . '%') : '';
                            } catch (\Exception $e) {}
                        }
                    }

                    // --- CORRECCIÓN EJECUCIÓN (AVANCE) ---
                    $avance = array_fill(0, 12, "");

                    // Cubrimos 'execution_progress' (camelCase o snake_case)
                    $progress = $activity['execution_progress']
                        ?? $activity['executionProgress']
                        ?? [];

                    if (is_array($progress)) {
                        foreach ($progress as $e) {
                            // Algunos arrays traen 'date' y otros 'month'
                            $dateRef = $e['month'] ?? $e['date'] ?? null;

                            if ($dateRef) {
                                try {
                                    $m = Carbon::parse($dateRef)->month - 1;
                                    // Buscamos 'reported_percentage' O 'percentage' simple
                                    $val = $e['reported_percentage'] ?? $e['percentage'] ?? 0;
                                    $avance[$m] = (is_numeric($val) && $val > 0) ? ($val . '%') : '';
                                } catch (\Exception $e) {}
                            }
                        }
                    }

                    $rowData = array_merge([
                        $labelActividad,
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
                    $sheet->getStyle("H{$row}:AE{$row}")->getAlignment()->setHorizontal('center');
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

        // 1. FILTRAR
        $productsRaw = $this->applyFilters($productsRaw, $request);

        $prioridadFuentes = [
            'INVERSIÓN EXTERNA' => 1, 'INVERSION EXTERNA' => 1,
            'FIASA' => 2,
            'GASTO CORRIENTE' => 3
        ];

        $groupedProducts = collect($productsRaw)->groupBy(function ($item) {
            return $item['location']['name'] ?? 'Sin Locación';
        });

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($groupedProducts as $locationName => $productsList) {
            $isAdmCentral = ($locationName === 'ADM. CENTRAL' || $locationName === 'ADM CENTRAL');

            $labelRubro     = $isAdmCentral ? "Dirección/Unidad: " : "Rubro: ";
            $labelProducto  = $isAdmCentral ? "Gestión: "          : "Producto: ";
            $labelActividad = $isAdmCentral ? "Entregable: "       : "Actividad: ";

            // CORRECCIÓN TAMBIÉN AQUÍ
            $products = collect($productsList)->sort(function ($a, $b) use ($prioridadFuentes) {
                $rubroA = (isset($a['rubro']) && is_array($a['rubro'])) ? ($a['rubro']['id'] ?? 0) : 0;
                $rubroB = (isset($b['rubro']) && is_array($b['rubro'])) ? ($b['rubro']['id'] ?? 0) : 0;

                $cmpRubro = $rubroA <=> $rubroB;
                if ($cmpRubro !== 0) {
                    return $cmpRubro;
                }

                $valA = $a['fund_source'] ?? $a['budget_type'] ?? '';
                $valB = $b['fund_source'] ?? $b['budget_type'] ?? '';

                $fuenteA = mb_strtoupper(trim(is_array($valA)?($valA['name']??''):$valA));
                $fuenteB = mb_strtoupper(trim(is_array($valB)?($valB['name']??''):$valB));

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
            $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']]);
            $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
            $sheet->mergeCells('A2:AI2');

            $headers = [
                $isAdmCentral ? "Gestión / Entregable" : "Producto / Actividad",
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

                $sheet->setCellValue("A{$row}", $labelProducto . ($product['name'] ?? ''));
                $sheet->setCellValue("E{$row}", $product['budget'] ?? 0);
                $rawSource = $product['fund_source'] ?? $product['budget_type'] ?? '';
                $sheet->setCellValue("F{$row}", is_array($rawSource) ? ($rawSource['name'] ?? '') : $rawSource);

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
                        if (!is_array($activity)) continue;

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
                                    $val = $p['planning'] ?? $p['percentage'] ?? 0;
                                    $plan[$m] = (is_numeric($val) && $val > 0) ? ($val . '%') : '';
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
                                        $val = $e['reported_percentage'] ?? 0;
                                        $avance[$m] = (is_numeric($val) && $val > 0) ? ($val . '%') : '';
                                    } catch (\Exception $e) {}
                                }
                            }
                        }

                        $rowData = array_merge([
                            $labelActividad,
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
                        $sheet->getStyle("H{$row}:AE{$row}")->getAlignment()->setHorizontal('center');
                        $row++;
                    }
                }
            }

            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(50);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(20);

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
