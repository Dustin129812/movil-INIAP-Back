<?php

namespace Modules\TrlImporter\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    public function processExcel(UploadedFile $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getPathname());

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $cols = [
            'estacion'        => 'A',
            'region'          => 'B',
            'investigador'    => 'C',
            'nombre'          => 'I',
            'rubro'           => 'J',
            'tipo_tecnologia' => 'Q',
            'aplica_trl'      => 'S',
            'trl_base'        => 'T',
        ];

        $registros = [];
        $stats = ['insertados' => 0, 'omitidos' => 0];

        for ($row = 2; $row <= $highestRow; $row++) {

            $nombre = trim((string) $sheet->getCell($cols['nombre'] . $row)->getValue());

            if (empty($nombre)) {
                $stats['omitidos']++;
                continue;
            }

            $aplicaTrlRaw = (string) $sheet->getCell($cols['aplica_trl'] . $row)->getValue();
            $aplicaTrlNormalizado = strtoupper(trim($aplicaTrlRaw));

            if (!in_array($aplicaTrlNormalizado, ['SI', 'SÍ', '1', 'TRUE'])) {
                $stats['omitidos']++;
                continue;
            }

            $registros[] = [
                'id'              => (string) Str::uuid(),
                'estacion'        => trim($sheet->getCell($cols['estacion'] . $row)->getValue()) ?: 'N/A',
                'region'          => trim($sheet->getCell($cols['region'] . $row)->getValue()) ?: 'N/A',
                'rubro'           => trim($sheet->getCell($cols['rubro'] . $row)->getValue()) ?: 'N/A',
                'investigador'    => trim($sheet->getCell($cols['investigador'] . $row)->getValue()) ?: 'N/A',
                'nombre'          => $nombre,
                'tipo_tecnologia' => trim($sheet->getCell($cols['tipo_tecnologia'] . $row)->getValue()) ?: 'N/A',
                'trl_base'        => (int) trim($sheet->getCell($cols['trl_base'] . $row)->getValue() ?: 0),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            $stats['insertados']++;
        }

        DB::transaction(function () use ($registros) {
            foreach (array_chunk($registros, 500) as $bloque) {
                DB::table('trl.tecnologias')->insert($bloque);
            }
        });

        return [
            'success' => true,
            'message' => "Se importaron {$stats['insertados']} tecnologías al catálogo central. Filas omitidas o no aplicables: {$stats['omitidos']}.",
            'data'    => $stats
        ];
    }
}
