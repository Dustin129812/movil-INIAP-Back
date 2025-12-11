<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;
use App\Modules\Planificacion\Http\Controllers\PlannerController;

class ExportController extends Controller
{
    public function exportPlanificacion(Request $request)

    {$response = app(PlannerController::class)->getProductsWithActivities($request)->getData(true);

        $products = $response['data']['products'] ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Agregar título
        $sheet->setCellValue('A1', 'Reporte de Planificacion POA');
        $sheet->mergeCells('A1:AI1'); // Unir columnas para el título
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
        ]);

        // Agregar fecha de generación
        $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y'));
        $sheet->mergeCells('A2:AI2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);

        //agrega la locacion
        $sheet->setCellValue('A3', 'Locación: ' . auth()->user()->location['name'] ?? 'Sistema');
        $sheet->mergeCells('A3:AI3');
        $sheet->getStyle('A3')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);
        // Agregar usuario que genera el reporte
        $sheet->setCellValue('A4', 'Generado por: ' . auth()->user()->name ?? 'Sistema');
        $sheet->mergeCells('A4:AI4');
        $sheet->getStyle('A4')->applyFromArray([
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center']
        ]);


        // ENCABEZADOS

        $headers = [
            "Producto / Actividad", "Descripción", "Responsable", "Indicadores",
            "Presupuesto", "Fuente Financiamiento", "Presupuesto Utilizado",
            // 12 meses planificado
            "Plan Ene","Plan Feb","Plan Mar","Plan Abr","Plan May","Plan Jun",
            "Plan Jul","Plan Ago","Plan Sep","Plan Oct","Plan Nov","Plan Dic",
            // 12 meses avance
            "Avance Ene","Avance Feb","Avance Mar","Avance Abr","Avance May","Avance Jun",
            "Avance Jul","Avance Ago","Avance Sep","Avance Oct","Avance Nov","Avance Dic",
            "Observaciones"
        ];

        $sheet->fromArray($headers, null, 'A6');

        // Estilos del header
        $sheet->getStyle('A6:AF6')->applyFromArray([
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

        $row = 7;
        $lastRubroId = null; // Para saber cuándo cambia de rubro

        foreach ($products as $product) {
            if (!empty($product['rubro']) && $product['rubro']['id'] !== $lastRubroId) {
        $sheet->setCellValue("A{$row}", "Rubro: " . $product['rubro']['name']);
        $sheet->mergeCells("A{$row}:AG{$row}"); // Unir columnas
        $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '68A829'] // color de rubro
            ],
            'alignment' => ['vertical' => 'center']
        ]);
        $row++;

        $lastRubroId = $product['rubro']['id']; // Guardamos el rubro actual
    }


            // FILA DEL PRODUCTO

            $sheet->setCellValue("A{$row}", "Producto: " . $product['name']);
            $sheet->setCellValue("E{$row}", $product['budget']);

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
                    $plan[$m] = $p['percentage'];
                }

                // Mapear avance (12 meses)
                $avance = array_fill(0, 12, "");
                foreach ($activity['execution_progress'] as $e) {
                    $m = Carbon::parse($e['month'])->month - 1;
                    $avance[$m] = $e['reported_percentage'];
                }

                $rowData = array_merge([
                    "Actividad: " . $activity['description'],
                    $activity['description'],
                    $responsables,
                    $indicadores,
                    $activity['budget'], // presupuesto
                    "", // fuente solo producto
                    "", // presupuesto usado
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
}
