<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Acuerdo;

class AcuerdoService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Acuerdo::query()->with(['organizacion']);

        if (!empty($filters['organizacion_id'])) {
            $query->where('organizacion_id', $filters['organizacion_id']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderByDesc('fecha_firma')->paginate($perPage);
    }

    public function create(array $data): Acuerdo
    {
        if (isset($data['archivo_acuerdo']) && $data['archivo_acuerdo'] instanceof \Illuminate\Http\UploadedFile) {
            $data['archivo_acuerdo_path'] = $data['archivo_acuerdo']->store('transferencia/acuerdos', 'private');
            unset($data['archivo_acuerdo']);
        }

        return Acuerdo::create($data)->load('organizacion');
    }

    public function update(Acuerdo $acuerdo, array $data): Acuerdo
    {
        if (isset($data['archivo_acuerdo']) && $data['archivo_acuerdo'] instanceof \Illuminate\Http\UploadedFile) {

            // Eliminar el archivo antiguo si existe
            if ($acuerdo->archivo_acuerdo_path && Storage::disk('private')->exists($acuerdo->archivo_acuerdo_path)) {
                Storage::disk('private')->delete($acuerdo->archivo_acuerdo_path);
            }

            $data['archivo_acuerdo_path'] = $data['archivo_acuerdo']->store('transferencia/acuerdos', 'private');
            unset($data['archivo_acuerdo']);
        }

        $acuerdo->update($data);

        return $acuerdo->load('organizacion');
    }

    public function delete(Acuerdo $acuerdo): bool
    {
        // Eliminación lógica (SoftDelete) preservando el archivo en storage
        return $acuerdo->delete();
    }
}
