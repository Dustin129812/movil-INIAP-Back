<?php

namespace Modules\Produccion\Entities;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibroCampoService
{
    public function crearLibroCampo(array $datos): LibroCampo
    {
        return DB::transaction(function () use ($datos) {

            $datos['qr_token'] = Str::uuid()->toString();

            return LibroCampo::create($datos);
        });
    }

    /**
     * Invalida el QR viejo generando uno nuevo de forma segura.
     */
    public function regenerarQrLibro(int $id): LibroCampo
    {
        $libro = LibroCampo::findOrFail($id);

        $libro->qr_token = Str::uuid()->toString();
        $libro->save();

        return $libro;
    }
}
