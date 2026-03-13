<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Acuerdo;

class AcuerdoService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Ensayo::query()
            ->with(['equipoTecnico'])
            ->withCount('parcelas');

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                ->orWhere('nombre_tecnologia', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['estado'])) { $query->where('estado', $filters['estado']); }
        if (!empty($filters['tipo'])) { $query->where('tipo', $filters['tipo']); }

        if (!empty($filters['provincia_id']) || !empty($filters['canton_id']) || !empty($filters['parroquia_id'])) {
            $query->whereHas('parcelas', function ($q) use ($filters) {
                if (!empty($filters['provincia_id'])) {
                    $q->where('provincia_id', $filters['provincia_id']);
                }
                if (!empty($filters['canton_id'])) {
                    $q->where('canton_id', $filters['canton_id']);
                }
                if (!empty($filters['parroquia_id'])) {
                    $q->where('parroquia_id', $filters['parroquia_id']);
                }
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderByDesc('created_at')->paginate($perPage);
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
