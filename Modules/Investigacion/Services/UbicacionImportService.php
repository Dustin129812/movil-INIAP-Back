<?php

namespace Modules\Investigacion\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;

class UbicacionImportService
{
    public function importDpaCsv(UploadedFile $file): array
    {
        return DB::transaction(function () use ($file) {
            $path = $file->getRealPath();
            $handle = fopen($path, 'r');

            // Ajusta el delimitador según tu CSV (',', ';', o '\t')
            $headers = fgetcsv($handle, 1000, ',');
            $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);

            $stats = ['provinces' => 0, 'cantons' => 0, 'parroquias' => 0];

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($headers) !== count($data)) continue;
                $row = array_combine($headers, $data);

                // Normalizamos los nombres del CSV (quitamos espacios extras)
                $nombrePro = trim($row['DPA_DESPRO']);
                $nombreCan = trim($row['DPA_DESCAN']);
                $nombrePar = trim($row['DPA_DESPAR']);

                // 1. Sincronizamos Provincia
                $provincia = $this->syncProvincia($row['DPA_PROVIN'], $nombrePro);

                // 2. Sincronizamos Cantón (y le inyectamos el ID de la provincia)
                $canton = $this->syncCanton($provincia->id, $row['DPA_CANTON'], $nombreCan);

                // 3. Sincronizamos Parroquia (y le inyectamos el ID del cantón)
                $this->syncParroquia($canton->id, $row['DPA_PARROQ'], $nombrePar);

                $stats['parroquias']++;
            }

            fclose($handle);

            $stats['provincias'] = Province::count();
            $stats['cantones'] = Canton::count();

            return $stats;
        });
    }

    private function syncProvincia(string $codigo, string $nombre): Province
    {
        // Buscamos primero por código (por si ya se actualizó) o por nombre exacto/similar
        $provincia = Province::where('codigo_inec', $codigo)
            ->orWhere('nombre', 'ilike', $nombre)
            ->first();

        if ($provincia) {
            $provincia->update(['codigo_inec' => $codigo, 'nombre' => $nombre]);
            return $provincia;
        }

        return Province::create(['codigo_inec' => $codigo, 'name' => $nombre]);
    }

    private function skipOrSyncCanton(int $provinciaId, string $codigo, string $nombre, array &$stats): Canton
    {
        $canton = Canton::where('codigo_inec', $codigo)
            ->orWhere(function ($query) use ($nombre, $provinciaId) {
                $query->where('name', 'ilike', $nombre)
                    // 1. Debe decir provincia_id aquí
                    ->where('provincia_id', $provinciaId);
            })
            ->first();

        if ($canton) {
            // 2. Debe decir provincia_id aquí y en el update
            if ($canton->codigo_inec !== $codigo || $canton->provincia_id !== $provinciaId) {
                $canton->update(['codigo_inec' => $codigo, 'provincia_id' => $provinciaId]);
            }
            return $canton;
        }

        $stats['cantones']++;
        return Canton::create([
            // 3. Debe decir provincia_id aquí en el create
            'provincia_id' => $provinciaId,
            'codigo_inec' => $codigo,
            'name' => $nombre
        ]);
    }

    private function syncParroquia(int $cantonId, string $codigo, string $nombre): Parroquia
    {
        $parroquia = Parroquia::where('codigo_inec', $codigo)
            ->orWhere('nombre', 'ilike', $nombre)
            ->first();

        if ($parroquia) {
            $parroquia->update([
                'codigo_inec' => $codigo,
                'nombre' => $nombre,
                'canton_id' => $cantonId // Aquí sanamos la base de datos
            ]);
            return $parroquia;
        }

        return Parroquia::create([
            'canton_id' => $cantonId,
            'codigo_inec' => $codigo,
            'nombre' => $nombre
        ]);
    }
}
