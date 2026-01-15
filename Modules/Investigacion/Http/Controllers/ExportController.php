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

    {$response = app(PlannerController::class)->getProductsWithActivities($request)->getData(true);

        $products = $response['data']['products'] ?? [];

        $products = collect($products)->sortBy('rubro.id')->values()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Agrega título
        $sheet->setCellValue('A1', 'Reporte de Planificacion POA');
        $sheet->mergeCells('A1:AI1'); // Unir columnas
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
        ]);

        // Agrega fecha de generación
        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->mergeCells('A2:AI2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);

        //Agrega la locacion
        $sheet->setCellValue('A3', 'Locación: ' . auth()->user()->location['name'] ?? 'Sistema');
        $sheet->mergeCells('A3:AI3');
        $sheet->getStyle('A3')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);
        // Agrega usuario que genera el reporte
        $sheet->setCellValue('A4', 'Generado por: ' . auth()->user()->name ?? 'Sistema');
        $sheet->mergeCells('A4:AI4');
        $sheet->getStyle('A4')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);
        $sheet->setCellValue('A5', 'Objetivo: ');
        $sheet->mergeCells('A5:AI5');
        $sheet->getStyle('A5')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);
        $sheet->setCellValue('A6', 'Director: ');
        $sheet->mergeCells('A6:AI6');
        $sheet->getStyle('A6')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);


        // ENCABEZADOS

        $headers = [
            "Producto / Actividad", "Descripción", "Responsable", "Indicadores",
            "Presupuesto", "Fuente Financiamiento", "Presupuesto Ejecutado",
            // 12 meses planificado
            "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
            "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
            // 12 meses avance
            "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
            "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
            "Observaciones"
        ];

        $sheet->fromArray($headers, null, 'A8');

        // Estilos del header
        $sheet->getStyle('A8:AF8')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'F2F3F2']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '008000']
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        $row = 9;
        $lastRubroId = null; // Para saber cuándo cambia de rubro

        foreach ($products as $product) {
            // Verifica si cambiamos de rubro
            if (!empty($product['rubro']) && $product['rubro']['id'] !== $lastRubroId) {
                $sheet->setCellValue("A{$row}", "Rubro: " . $product['rubro']['name']);
                $sheet->mergeCells("A{$row}:AF{$row}");
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => '68A829']
                    ],
                    'alignment' => ['vertical' => 'center']
                ]);
                $row++;
                $lastRubroId = $product['rubro']['id'];
            }

            // FILA DEL PRODUCTO

            $sheet->setCellValue("A{$row}", "Producto: " . $product['name']);
            $sheet->setCellValue("E{$row}", $product['budget']);
            $sheet->setCellValue("F{$row}", $product['budget_type']);

            // Estilos producto (azul claro)
            $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '949994']
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'alignment' => ['vertical' => 'center']
            ]);

            $row++;


            // ACTIVIDADES DE ESTE PRODUCTO

            foreach ($product['activities'] as $activity) {

                $responsables = implode(", ", array_column($activity['users'], 'name'));
                $indicadores = implode(", ", array_column($activity['indicators'], 'name'));

                // Mapear planificado (12 meses)
                $plan = array_fill(0, 12, "");
                foreach ($activity['monthly_plannig'] as $p) {
                    $m = Carbon::parse($p['month'])->month - 1;
                    $plan[$m] = $p['percentage']."%";
                }

                // Mapear avance (12 meses)
                $avance = array_fill(0, 12, "");
                foreach ($activity['execution_progress'] as $e) {
                    $m = Carbon::parse($e['month'])->month - 1;
                    $avance[$m] = $e['reported_percentage']."%";
                }

                $rowData = array_merge([
                    "Actividad: ",
                    $activity['description'],
                    $responsables,
                    $indicadores,
                    $activity['budget'], // presupuesto
                    "", // fuente solo producto
                    $activity['accrued_budget'], // presupuesto usado
                ], $plan, $avance, [
                    "" // observaciones
                ]);

                $sheet->fromArray($rowData, null, "A{$row}");

                // Estilos fila actividad
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'vertical' => 'center',
                        'wrapText' => true
                    ]
                ]);

                $row++;
            }
        }

        //Definimos el ancho de cada tambla
        $sheet->getColumnDimension('A')->setWidth(40); // Producto / Actividad
        $sheet->getColumnDimension('B')->setWidth(50); // Descripción
        $sheet->getColumnDimension('C')->setWidth(25); // Responsable
        $sheet->getColumnDimension('D')->setWidth(30); // Indicadores
        $sheet->getColumnDimension('E')->setWidth(15); // Presupuesto
        $sheet->getColumnDimension('F')->setWidth(20); // Fuente
        $sheet->getColumnDimension('G')->setWidth(15); // Presupuesto Utilizado

        //Se busca el rango de los meses de planificacion y avance
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        //Establecemos el ancho para todos los meses
        for ($col = 8; $col <= $highestColumnIndex; $col++) {
            $colString = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colString)->setWidth(12); // Ancho para números/porcentajes
        }

        //Asegura que TODAS las celdas tengan wrapText activado
        $lastRow = $row - 1;
        $sheet->getStyle("A6:{$highestColumn}{$lastRow}")
                ->getAlignment()->setWrapText(true);

        // Descargar Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = "reporte_productos_actividades.xlsx";
        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportPlanificacionAllLocations(Request $request)
{
    $response = app(PlannerController::class)->getAllProductsWithActivities($request)->getData(true);
    $productsRaw = $response['data']['products'] ?? [];

    $groupedProducts = collect($productsRaw)->groupBy(function ($item) {
        return $item['location']['name'] ?? 'Sin Locación';
    });

    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0); // Eliminamos la hoja en blanco inicial

    foreach ($groupedProducts as $locationName => $products) {
        //Crear una nueva hoja para cada locación
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(substr($locationName, 0, 31)); // Excel limita nombres a 31 caracteres

        $products = $products->sortBy('rubro.id')->values()->all();

        // Título y Encabezados de información
        $sheet->setCellValue('A1', 'Reporte de Planificación POA - ' . $locationName);
        $sheet->mergeCells('A1:AI1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center']
        ]);

        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->setCellValue('A3', 'Locación: ' . $locationName);
        $sheet->setCellValue('A4', 'Generado por: ' . (auth()->user()->name ?? 'Sistema'));


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
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '008000']],
            'alignment' => ['horizontal' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);

        $row = 9;
        $lastRubroId = null;

        foreach ($products as $product) {
            // Lógica de Rubros
            if (!empty($product['rubro']) && $product['rubro']['id'] !== $lastRubroId) {
                $sheet->setCellValue("A{$row}", "Rubro: " . $product['rubro']['name']);
                $sheet->mergeCells("A{$row}:AF{$row}");
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '68A829']]
                ]);
                $row++;
                $lastRubroId = $product['rubro']['id'];
            }

            // Fila de Producto
            $sheet->setCellValue("A{$row}", "Producto: " . $product['name']);
            $sheet->setCellValue("E{$row}", $product['budget']);
            $sheet->setCellValue("F{$row}", $product['budget_type']);
            $sheet->getStyle("A{$row}:AF{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('949994');
            $row++;

            // Actividades
            foreach ($product['activities'] as $activity) {
                $responsables = implode(", ", array_column($activity['users'], 'name'));
                $indicadores = implode(", ", array_column($activity['indicators'], 'name'));

                // Mapeo de meses
                $plan = array_fill(0, 12, "");
                foreach ($activity['monthly_plannig'] as $p) {
                    $m = Carbon::parse($p['month'])->month - 1;
                    $plan[$m] = $p['percentage']."%";
                }

                $avance = array_fill(0, 12, "");
                foreach ($activity['execution_progress'] as $e) {
                    $m = Carbon::parse($e['month'])->month - 1;
                    $avance[$m] = $e['reported_percentage']."%";
                }

                $rowData = array_merge([
                    "Actividad: ", $activity['description'], $responsables, $indicadores,
                    $activity['budget'], "", $activity['accrued_budget']
                ], $plan, $avance, [""]);

                $sheet->fromArray($rowData, null, "A{$row}");
                $row++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(40); // Producto / Actividad
        $sheet->getColumnDimension('B')->setWidth(50); // Descripción
        $sheet->getColumnDimension('C')->setWidth(25); // Responsable
        $sheet->getColumnDimension('D')->setWidth(30); // Indicadores
        $sheet->getColumnDimension('E')->setWidth(15); // Presupuesto
        $sheet->getColumnDimension('F')->setWidth(20); // Fuente
        $sheet->getColumnDimension('G')->setWidth(15); // Presupuesto Utilizado


        $sheet->getStyle("A1:AF{$row}")->getAlignment()->setWrapText(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $fileName = "reporte_consolidado_locaciones.xlsx";
    $filePath = storage_path("app/public/{$fileName}");
    $writer->save($filePath);

    return response()->download($filePath)->deleteFileAfterSend(true);
}
}
