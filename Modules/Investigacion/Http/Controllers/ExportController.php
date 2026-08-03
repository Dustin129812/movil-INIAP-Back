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

            $fundingSourceName = $request->get('fundingSourceName');
            if (!empty($fundingSourceName)) {
                $pFundingSource = $product['funding_source_name'] ?? '';

                if (mb_strtolower(trim($pFundingSource)) !== mb_strtolower(trim($fundingSourceName))) {
                    return false;
                }
            }

            return true;
        })->values()->all();
    }

    public function exportPlanificacion(Request $request)
    {
        // 1. OXÍGENO PARA PHP: Ampliamos memoria y tiempo solo para esta ejecución
        ini_set('memory_limit', '512M'); // Súbelo a '1G' si sigue fallando a futuro
        set_time_limit(300); // 5 minutos de tiempo de ejecución

        $response = app(PlannerController::class)->getProductsWithActivities($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $dataRaw = $response->getData(true);
        } elseif ($response instanceof \Illuminate\Contracts\Support\Arrayable) {
            $dataRaw = $response->toArray();
        } else {
            $dataRaw = (array) $response;
        }

        $productsSource = $dataRaw['data']['products'] ?? $dataRaw['products'] ?? $dataRaw['data'] ?? $dataRaw;
        $products = json_decode(json_encode($productsSource), true);
        if (!is_array($products)) $products = [];

        // 2. GARBAGE COLLECTION: Liberamos la memoria de la respuesta original pesada
        unset($response, $dataRaw, $productsSource);

        $products = $this->applyFilters($products, $request);

        $groupedProducts = collect($products)->groupBy(function ($item) {
            $rawSource = $item['budget_type'] ?? '';
            $sourceName = is_array($rawSource) ? ($rawSource['name'] ?? '') : $rawSource;
            return !empty($sourceName) ? mb_strtoupper(trim($sourceName)) : 'SIN TIPO DEFINIDO';
        });

        // 3. GARBAGE COLLECTION: Liberamos el array plano, ya que ahora usamos la colección agrupada
        unset($products);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $userLocationName = auth()->user()->location['name'] ?? 'Sistema';
        $isAdmCentral = ($userLocationName === 'ADM. CENTRAL' || $userLocationName === 'ADM CENTRAL');

        $labelRubro     = $isAdmCentral ? "Dirección/Unidad: " : "Rubro: ";
        $labelProducto  = $isAdmCentral ? "Gestión: "          : "Producto: ";
        $labelActividad = $isAdmCentral ? "Entregable: "       : "Actividad: ";

        foreach ($groupedProducts as $financingName => $productsList) {

            $sheetProducts = collect($productsList)->sort(function ($a, $b) {
                $rubroA = $a['rubro']['id'] ?? 0;
                $rubroB = $b['rubro']['id'] ?? 0;
                return $rubroA <=> $rubroB;
            })->values()->all();

            $totalesPorRubro = collect($sheetProducts)->groupBy(function($item){
                return $item['rubro']['id'] ?? 0;
            })->map(function ($items) {
                return $items->sum('budget');
            });

            $sheet = $spreadsheet->createSheet();
            $safeSheetName = preg_replace('/[*\:\/\\\\\?\[\]]/', '', $financingName);
            $sheet->setTitle(substr($safeSheetName, 0, 31));

            $sheet->setCellValue('A1', 'Reporte de Planificacion POA - ' . $financingName);
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
                "Presupuesto", "Fuente Financiamiento", "Nombre del Proyecto", "Presupuesto Ejecutado",
                "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
                "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
                "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
                "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
                "Observaciones"
            ];
            $sheet->fromArray($headers, null, 'A8');

            $sheet->getStyle('A8:AG8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'F2F3F2']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ]);

            $row = 9;
            $lastRubroId = null;

            foreach ($sheetProducts as $product) {
                $rubro = $product['rubro'] ?? [];
                $currentRubroId = $rubro['id'] ?? 0;

                if ($currentRubroId !== $lastRubroId) {
                    $sheet->setCellValue("A{$row}", $labelRubro . ($rubro['name'] ?? 'Sin Clasificación'));
                    $sheet->mergeCells("A{$row}:D{$row}");
                    $sheet->setCellValue("E{$row}", $totalesPorRubro[$currentRubroId] ?? 0);

                    $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']],
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

                $sheet->setCellValue("G{$row}", $product['funding_source_name'] ?? '');

                $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F497D']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
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
                        $planners = (array) ($activity['planners'] ?? $activity['monthly_planning'] ?? $activity['monthly_progress'] ?? []);
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
                        $progress = $activity['execution_progress'] ?? $activity['executionProgress'] ?? [];
                        if (is_array($progress)) {
                            foreach ($progress as $e) {
                                $dateRef = $e['month'] ?? $e['date'] ?? null;
                                if ($dateRef) {
                                    try {
                                        $m = Carbon::parse($dateRef)->month - 1;
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
                            "",
                            $activity['accrued_budget'] ?? 0,
                        ], $plan, $avance, [""]);

                        $sheet->fromArray($rowData, null, "A{$row}");
                        $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                            'alignment' => ['vertical' => 'center', 'wrapText' => true]
                        ]);
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                        $sheet->getStyle("I{$row}:AF{$row}")->getAlignment()->setHorizontal('center');
                        $row++;
                    }
                }
            }

            // Dimensiones
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(50);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(25);

            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($col = 9; $col <= $highestColumnIndex; $col++) {
                $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colString)->setWidth(12);
            }

            $lastRow = max($row - 1, 8);
            $sheet->getStyle("A8:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'reporte_productos_actividades.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportPlanificacionAllLocations(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $response = app(PlannerController::class)->getAllProductsWithActivities($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $dataRaw = $response->getData(true);
        } elseif ($response instanceof \Illuminate\Contracts\Support\Arrayable) {
            $dataRaw = $response->toArray();
        } else {
            $dataRaw = (array) $response;
        }

        $productsRaw = $dataRaw['data']['products'] ?? $dataRaw['products'] ?? $dataRaw['data'] ?? $dataRaw;
        $productsRaw = json_decode(json_encode($productsRaw), true);
        if (!is_array($productsRaw)) $productsRaw = [];

        // 1. FILTRAR
        $productsRaw = $this->applyFilters($productsRaw, $request);

        // 2. AGRUPAR POR UBICACIÓN (ESTO CREARÁ UNA HOJA POR CADA ESTACIÓN)
        $groupedByLocation = collect($productsRaw)->groupBy(function ($item) {
            return $item['location']['name'] ?? 'SIN UBICACIÓN';
        });

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($groupedByLocation as $locationName => $productsList) {

            // 3. ORDENAR DATOS DENTRO DE LA HOJA
            // Primero por Fuente de Financiamiento (Budget Type), luego por Rubro
            $products = collect($productsList)->sort(function ($a, $b) {
                // Criterio 1: Tipo de Financiamiento
                $sourceA = is_array($a['budget_type'] ?? '') ? ($a['budget_type']['name'] ?? '') : ($a['budget_type'] ?? '');
                $sourceB = is_array($b['budget_type'] ?? '') ? ($b['budget_type']['name'] ?? '') : ($b['budget_type'] ?? '');
                $cmpSource = strcmp($sourceA, $sourceB);
                if ($cmpSource !== 0) return $cmpSource;

                // Criterio 2: Rubro
                $rubroA = (isset($a['rubro']) && is_array($a['rubro'])) ? ($a['rubro']['id'] ?? 0) : 0;
                $rubroB = (isset($b['rubro']) && is_array($b['rubro'])) ? ($b['rubro']['id'] ?? 0) : 0;
                return $rubroA <=> $rubroB;
            })->values()->all();

            // Calculamos subtotales agrupando por Financiamiento + Rubro
            $totalesPorGrupo = collect($products)->groupBy(function($item){
                $source = is_array($item['budget_type'] ?? '') ? ($item['budget_type']['name'] ?? '') : ($item['budget_type'] ?? '');
                $rubroId = $item['rubro']['id'] ?? 0;
                return $source . '_' . $rubroId;
            })->map(function ($items) {
                return $items->sum('budget');
            });

            $sheet = $spreadsheet->createSheet();
            // Nombre de la hoja = Nombre de la Estación (limpio)
            $safeSheetName = preg_replace('/[*\:\/\\\\\?\[\]]/', '', $locationName);
            $sheet->setTitle(substr($safeSheetName, 0, 31));

            // ENCABEZADOS DE HOJA
            $sheet->setCellValue('A1', 'Reporte Consolidado POA - ' . $locationName);
            $sheet->mergeCells('A1:AI1');
            $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']]);
            $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
            $sheet->mergeCells('A2:AI2');

            // COLUMNAS (Incluye la nueva columna G: Nombre del Proyecto)
            $headers = [
                "Producto / Actividad",
                "Descripción", "Responsable", "Indicadores",
                "Presupuesto", "Fuente Financiamiento", "Nombre del Proyecto", "Presupuesto Ejecutado",
                "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
                "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
                "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
                "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
                "Observaciones"
            ];
            $sheet->fromArray($headers, null, 'A8');

            $sheet->getStyle('A8:AG8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'F2F3F2']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $row = 9;
            $lastGroupKey = null;

            foreach ($products as $product) {
                $rubro = $product['rubro'] ?? [];
                $rawSource = $product['budget_type'] ?? '';
                $sourceName = is_array($rawSource) ? ($rawSource['name'] ?? '') : $rawSource;

                // Llave única para separar visualmente
                $groupKey = $sourceName . '_' . ($rubro['id'] ?? 0);

                if ($groupKey !== $lastGroupKey) {
                    $rubroName = $rubro['name'] ?? 'Sin Rubro';
                    $financiamientoLabel = !empty($sourceName) ? $sourceName : 'Sin Fuente';

                    $sheet->setCellValue("A{$row}", "Financiamiento: {$financiamientoLabel}  |  Rubro: {$rubroName}");
                    $sheet->mergeCells("A{$row}:D{$row}");

                    $sheet->setCellValue("E{$row}", $totalesPorGrupo[$groupKey] ?? 0);

                    $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']], // Verde claro
                        'alignment' => ['vertical' => 'center']
                    ]);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                    $row++;
                    $lastGroupKey = $groupKey;
                }

                // DATOS DEL PRODUCTO
                $sheet->setCellValue("A{$row}", "Producto: " . ($product['name'] ?? ''));
                $sheet->setCellValue("E{$row}", $product['budget'] ?? 0);
                $sheet->setCellValue("F{$row}", $sourceName);

                // Columna G: Nombre del Proyecto (Funding Source Name)
                $sheet->setCellValue("G{$row}", $product['funding_source_name'] ?? '');

                $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F497D']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']], // Gris azulado
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => 'center']
                ]);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                $row++;

                // ACTIVIDADES
                $activities = $product['activities'] ?? [];
                if (!empty($activities) && is_array($activities)) {
                    foreach ($activities as $activity) {
                        if (!is_array($activity)) continue;

                        $usersList = $activity['users'] ?? [];
                        $responsables = implode(", ", array_column($usersList, 'name'));
                        $indicatorsList = $activity['indicators'] ?? $activity['performance_indicators'] ?? [];
                        $indicadores = implode(", ", array_column($indicatorsList, 'name'));

                        // Planificación
                        $plan = array_fill(0, 12, "");
                        $planners = (array) ($activity['planners'] ?? $activity['monthly_planning'] ?? []);
                        foreach ($planners as $p) {
                            if (isset($p['month'])) {
                                try {
                                    $m = Carbon::parse($p['month'])->month - 1;
                                    $val = $p['planning'] ?? $p['percentage'] ?? 0;
                                    $plan[$m] = (is_numeric($val) && $val > 0) ? ($val / 100) : 0;
                                } catch (\Exception $e) {}
                            }
                        }

                        // Ejecución
                        $avance = array_fill(0, 12, "");
                        $progress = $activity['execution_progress'] ?? [];
                        if(is_array($progress)){
                            foreach ($progress as $e) {
                                if (isset($e['month'])) {
                                    try {
                                        $m = Carbon::parse($e['month'])->month - 1;
                                        $val = $e['reported_percentage'] ?? $e['percentage'] ?? 0;
                                        $avance[$m] = (is_numeric($val) && $val > 0) ? ($val / 100) : 0;
                                    } catch (\Exception $e) {}
                                }
                            }
                        }

                        $rowData = array_merge([
                            "Actividad:",
                            $activity['description'] ?? '',
                            $responsables,
                            $indicadores,
                            $activity['budget'] ?? 0,
                            "",
                            "",
                            $activity['accrued_budget'] ?? 0
                        ], $plan, $avance, [""]);

                        $sheet->fromArray($rowData, null, "A{$row}");
                        $sheet->getStyle("A{$row}:AG{$row}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['vertical' => 'center', 'wrapText' => true]
                        ]);
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
                        $sheet->getStyle("I{$row}:AF{$row}")->getAlignment()->setHorizontal('center');
                        $row++;
                    }
                }
            }

            // AJUSTE DE ANCHO DE COLUMNAS
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(50);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(25); // Nueva Columna Proyecto

            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            for ($col = 9; $col <= $highestColumnIndex; $col++) {
                $colString = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colString)->setWidth(12);
            }
            $lastRow = max($row - 1, 8);
            $sheet->getStyle("A8:{$highestColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'reporte_consolidado_locaciones.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
