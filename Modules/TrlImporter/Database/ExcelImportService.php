<?php

namespace Modules\TrlImporter\Database;

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
        $highestColumn = $sheet->getHighestColumn();

        $colsBase = [
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

        $cabecerasDinamicas = [];
        $colIndex = 'A';
        while ($colIndex !== $highestColumn) {
            if (!in_array($colIndex, array_values($colsBase))) {
                $cabecerasDinamicas[$colIndex] = trim((string) $sheet->getCell($colIndex . '1')->getValue());
            }
            $colIndex++;
        }
        if (!in_array($highestColumn, array_values($colsBase))) {
            $cabecerasDinamicas[$highestColumn] = trim((string) $sheet->getCell($highestColumn . '1')->getValue());
        }

        $stats = ['procesados' => 0, 'omitidos' => 0];
        $existentes = DB::table('trl.tecnologias')->pluck('id', 'nombre')->toArray();
        $registrosUpsert = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $nombreBruto = (string) $sheet->getCell($colsBase['nombre'] . $row)->getValue();
            $nombre = trim(preg_replace('/\s+/', ' ', $nombreBruto));

            if (empty($nombre)) {
                $stats['omitidos']++;
                continue;
            }

            $aplicaTrlNormalizado = strtoupper(trim((string) $sheet->getCell($colsBase['aplica_trl'] . $row)->getValue()));
            if (!in_array($aplicaTrlNormalizado, ['SI', 'SÍ', '1', 'TRUE'])) {
                $stats['omitidos']++;
                continue;
            }

            $metadata = [];
            foreach ($cabecerasDinamicas as $letraCol => $nombreHeader) {
                if (!empty($nombreHeader)) {
                    $metadata[$nombreHeader] = trim((string) $sheet->getCell($letraCol . $row)->getValue());
                }
            }

            $id = $existentes[$nombre] ?? (string) Str::uuid();

            $existentes[$nombre] = $id;

            $registrosUpsert[$id] = [
                'id'              => $id,
                'estacion'        => trim($sheet->getCell($colsBase['estacion'] . $row)->getValue()) ?: 'N/A',
                'region'          => trim($sheet->getCell($colsBase['region'] . $row)->getValue()) ?: 'N/A',
                'programa'        => trim($sheet->getCell($colsBase['programa'] . $row)->getValue()) ?: 'N/A',
                'rubro'           => trim($sheet->getCell($colsBase['rubro'] . $row)->getValue()) ?: 'N/A',
                'investigador'    => trim($sheet->getCell($colsBase['investigador'] . $row)->getValue()) ?: 'N/A',
                'nombre'          => $nombre,
                'tipo_tecnologia' => trim($sheet->getCell($colsBase['tipo_tecnologia'] . $row)->getValue()) ?: 'N/A',
                'trl_base'        => (int) trim($sheet->getCell($colsBase['trl_base'] . $row)->getValue() ?: 0),
                'metadata'        => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            $stats['procesados']++;
        }

        $loteLimpio = array_values($registrosUpsert);

        DB::transaction(function () use ($loteLimpio) {
            if (!empty($loteLimpio)) {
                foreach (array_chunk($loteLimpio, 500) as $bloque) {
                    DB::table('trl.tecnologias')->upsert(
                        $bloque,
                        ['id'],
                        ['estacion', 'region', 'programa', 'rubro', 'investigador', 'nombre', 'tipo_tecnologia', 'trl_base', 'metadata', 'updated_at']
                    );
                }
            }
        });

        return [
            'success' => true,
            'message' => "Sincronización completada. Registros procesados/actualizados: {$stats['procesados']} | Omitidos: {$stats['omitidos']}.",
            'data'    => $stats
        ];
    }
}
