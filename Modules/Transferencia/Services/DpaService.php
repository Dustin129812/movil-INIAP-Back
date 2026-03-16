<?php


namespace Modules\Transferencia\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;

class DpaService
{
    /**
     * Procesa el archivo CSV del DPA y construye la jerarquía geográfica.
     */
    public function importarDpaCsv($archivo): array
    {
        // 1. Forzamos a PHP a reconocer saltos de línea de Mac, Linux o Windows
        ini_set('auto_detect_line_endings', true);

        return DB::transaction(function () use ($archivo) {
            $fileHandle = fopen($archivo->getRealPath(), 'r');

            $stats = ['provincias' => 0, 'cantones' => 0, 'parroquias' => 0, 'lineas_leidas' => 0];

            while (($row = fgetcsv($fileHandle, 4000, ',')) !== false) {

                // 2. Soporte automático si el Excel del usuario guardó con punto y coma (;)
                if (count($row) === 1 && strpos($row[0], ';') !== false) {
                    $row = explode(';', $row[0]);
                }

                // 3. Extraemos las columnas clave según la estructura oficial del INEC
                $codProv = trim($row[1] ?? '');
                $nomProv = trim($row[4] ?? '');

                // 4. Búsqueda inteligente: Solo procesa si la fila tiene un código numérico y un nombre.
                // Esto ignora automáticamente las filas de título, cabeceras y líneas en blanco.
                if (empty($codProv) || !is_numeric($codProv) || empty($nomProv)) {
                    continue;
                }

                $stats['lineas_leidas']++; // Contamos que sí encontró una línea válida

                $codCant = trim($row[2] ?? '');
                $codParr = trim($row[3] ?? '');
                $nomCant = trim($row[5] ?? '');
                $nomParr = trim($row[6] ?? '');

                // Inserción en cascada con protección de duplicados
                $provincia = Province::firstOrCreate(
                    ['codigo_inec' => $codProv],
                    ['name' => $nomProv]
                );
                if ($provincia->wasRecentlyCreated) $stats['provincias']++;

                $canton = Canton::firstOrCreate(
                    ['codigo_inec' => $codCant, 'provincia_id' => $provincia->id],
                    ['name' => $nomCant]
                );
                if ($canton->wasRecentlyCreated) $stats['cantones']++;

                $parroquia = Parroquia::firstOrCreate(
                    ['codigo_inec' => $codParr, 'canton_id' => $canton->id],
                    ['nombre' => $nomParr]
                );
                if ($parroquia->wasRecentlyCreated) $stats['parroquias']++;
            }

            fclose($fileHandle);

            // Registramos la métrica real en Spatie ActivityLog
            activity('system')->log("Sincronización DPA: {$stats['provincias']} Provincias, {$stats['cantones']} Cantones, {$stats['parroquias']} Parroquias nuevas. (Líneas procesadas: {$stats['lineas_leidas']})");

            return $stats;
        });
    }
}
