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
            'programa'        => 'E',
            'nombre'          => 'I',
            'rubro'           => 'J',
            'tipo_tecnologia' => 'Q',
            'aplica_trl'      => 'S',
            'trl_base'        => 'T',
        ];

        $stats = ['insertados' => 0, 'actualizados' => 0, 'omitidos' => 0];

        $existentes = DB::table('trl.tecnologias')->pluck('id', 'nombre')->toArray();

        $paraInsertar = [];
        $paraActualizar = [];

        for ($row = 2; $row <= $highestRow; $row++) {

            $nombreBruto = (string) $sheet->getCell($cols['nombre'] . $row)->getValue();
            $nombre = trim(preg_replace('/\s+/', ' ', $nombreBruto));

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

            $datosFila = [
                'estacion'        => trim($sheet->getCell($cols['estacion'] . $row)->getValue()) ?: 'N/A',
                'region'          => trim($sheet->getCell($cols['region'] . $row)->getValue()) ?: 'N/A',
                'programa'        => trim($sheet->getCell($cols['programa'] . $row)->getValue()) ?: 'N/A',
                'rubro'           => trim($sheet->getCell($cols['rubro'] . $row)->getValue()) ?: 'N/A',
                'investigador'    => trim($sheet->getCell($cols['investigador'] . $row)->getValue()) ?: 'N/A',
                'nombre'          => $nombre,
                'tipo_tecnologia' => trim($sheet->getCell($cols['tipo_tecnologia'] . $row)->getValue()) ?: 'N/A',
                'trl_base'        => (int) trim($sheet->getCell($cols['trl_base'] . $row)->getValue() ?: 0),
                'updated_at'      => now(),
            ];

            if (array_key_exists($nombre, $existentes)) {
                $datosFila['id'] = $existentes[$nombre];
                $paraActualizar[] = $datosFila;
                $stats['actualizados']++;
            } else {
                $datosFila['id'] = (string) Str::uuid();
                $datosFila['created_at'] = now();
                $paraInsertar[] = $datosFila;

                $existentes[$nombre] = $datosFila['id'];
                $stats['insertados']++;
            }
        }

        DB::transaction(function () use ($paraInsertar, $paraActualizar) {

            if (!empty($paraInsertar)) {
                foreach (array_chunk($paraInsertar, 500) as $bloque) {
                    DB::table('trl.tecnologias')->insert($bloque);
                }
            }

            if (!empty($paraActualizar)) {
                foreach ($paraActualizar as $update) {
                    $id = $update['id'];
                    unset($update['id']); // Quitamos el ID del payload para evitar error en el query builder
                    DB::table('trl.tecnologias')->where('id', $id)->update($update);
                }
            }
        });

        return [
            'success' => true,
            'message' => "Proceso completado. Nuevos: {$stats['insertados']} | Actualizados: {$stats['actualizados']} | Omitidos: {$stats['omitidos']}.",
            'data'    => $stats
        ];
    }
}
