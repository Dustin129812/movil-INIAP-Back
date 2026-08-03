<?php

namespace Modules\Transferencia\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DpaService
{
    public function importar(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $hojaActiva = $spreadsheet->getSheet(0);
        $filas = $hojaActiva->toArray();

        return DB::transaction(function () use ($filas) {
            $stats = ['provincias' => 0, 'cantones' => 0, 'parroquias' => 0];

            $cleanCode = function($val) {
                if ($val === null || $val === '') return '';
                $stringVal = trim((string)$val);
                $sinDecimales = explode('.', $stringVal)[0];
                return preg_replace('/[^0-9]/', '', $sinDecimales);
            };

            foreach ($filas as $indice => $data) {
                $numeroFila = $indice + 1;

                if ($numeroFila < 4) {
                    continue;
                }

                if (!isset($data[2]) || trim((string)$data[2]) === '') {
                    continue;
                }

                $codProvRaw = $cleanCode($data[2]);
                $nombrePro  = trim((string)($data[3] ?? ''));

                $codCanRaw  = $cleanCode($data[4] ?? '');
                $nombreCan  = trim((string)($data[5] ?? ''));

                $codParRaw  = $cleanCode($data[6] ?? '');
                $nombrePar  = trim((string)($data[7] ?? ''));

                if (empty($codProvRaw) || strlen($codProvRaw) > 2) {
                    continue;
                }

                $codProvincia = str_pad($codProvRaw, 2, '0', STR_PAD_LEFT);
                $codCanton    = $codProvincia . str_pad($codCanRaw, 2, '0', STR_PAD_LEFT);
                $codParroquia = $codCanton . str_pad($codParRaw, 2, '0', STR_PAD_LEFT);

                $provincia = $this->skipOrSyncProvincia($codProvincia, $nombrePro, $stats);

                if (!empty($nombreCan) && !empty($codCanRaw)) {
                    $canton = $this->skipOrSyncCanton($provincia->id, $codCanton, $nombreCan, $stats);

                    if (!empty($nombrePar) && !empty($codParRaw)) {
                        $this->skipOrSyncParroquia($canton->id, $codParroquia, $nombrePar, $stats);
                    }
                }
            }

            return $stats;
        });
    }

    private function skipOrSyncProvincia(string $codigo, string $nombre, array &$stats): Province
    {
        $provincia = Province::where('codigo_inec', $codigo)
            ->orWhere('name', 'ilike', $nombre)
            ->first();

        if ($provincia) {
            if ($provincia->codigo_inec !== $codigo) {
                $provincia->update(['codigo_inec' => $codigo]);
            }
            return $provincia;
        }

        $stats['provincias']++;
        return Province::create(['codigo_inec' => $codigo, 'name' => $nombre]);
    }

    private function skipOrSyncCanton(int $provinciaId, string $codigo, string $nombre, array &$stats): Canton
    {
        $canton = Canton::where('codigo_inec', $codigo)
            ->orWhere(function ($query) use ($nombre, $provinciaId) {
                $query->where('name', 'ilike', $nombre)
                    // ¡Corregido a provincia_id!
                    ->where('provincia_id', $provinciaId);
            })
            ->first();

        if ($canton) {
            // ¡Corregido a provincia_id!
            if ($canton->codigo_inec !== $codigo || $canton->provincia_id !== $provinciaId) {
                $canton->update([
                    'codigo_inec' => $codigo,
                    'provincia_id' => $provinciaId // ¡Corregido a provincia_id!
                ]);
            }
            return $canton;
        }

        $stats['cantones']++;
        return Canton::create([
            'provincia_id' => $provinciaId,
            'codigo_inec' => $codigo,
            'name' => $nombre
        ]);
    }

    private function skipOrSyncParroquia(int $cantonId, string $codigo, string $nombre, array &$stats): Parroquia
    {
        $parroquia = Parroquia::where('codigo_inec', $codigo)
            ->orWhere(function ($query) use ($nombre, $cantonId) {
                $query->where('nombre', 'ilike', $nombre)
                    ->where('canton_id', $cantonId);
            })
            ->first();

        if ($parroquia) {
            if ($parroquia->codigo_inec !== $codigo || $parroquia->canton_id !== $cantonId) {
                $parroquia->update(['codigo_inec' => $codigo, 'canton_id' => $cantonId]);
            }
            return $parroquia;
        }

        $stats['parroquias']++;
        return Parroquia::create([
            'canton_id' => $cantonId,
            'codigo_inec' => $codigo,
            'nombre' => $nombre
        ]);
    }
}
