<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Ensayo;

class EnsayoService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Ensayo::query()->with(['equipoTecnico']);

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                ->orWhere('nombre_tecnologia', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['estado'])) { $query->where('estado', $filters['estado']); }
        if (!empty($filters['tipo'])) { $query->where('tipo', $filters['tipo']); }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Ensayo
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['archivo_protocolo']) && $data['archivo_protocolo'] instanceof \Illuminate\Http\UploadedFile) {
                $data['archivo_protocolo_path'] = $data['archivo_protocolo']->store('transferencia/protocolos', 'private');
                unset($data['archivo_protocolo']);
            }

            if (isset($data['archivo_informe']) && $data['archivo_informe'] instanceof \Illuminate\Http\UploadedFile) {
                $data['archivo_informe_path'] = $data['archivo_informe']->store('transferencia/informes', 'private');
                unset($data['archivo_informe']);
            }

            $ensayo = Ensayo::create($data);

            if (!empty($data['equipo_tecnico_ids'])) {
                $ensayo->equipoTecnico()->sync($data['equipo_tecnico_ids']);
            }

            return $ensayo->load(['equipoTecnico']);
        });
    }

    public function update(Ensayo $ensayo, array $data): Ensayo
    {
        return DB::transaction(function () use ($ensayo, $data) {
            // Lógica Protocolo
            if (isset($data['archivo_protocolo']) && $data['archivo_protocolo'] instanceof \Illuminate\Http\UploadedFile) {
                if ($ensayo->archivo_protocolo_path && Storage::disk('private')->exists($ensayo->archivo_protocolo_path)) {
                    Storage::disk('private')->delete($ensayo->archivo_protocolo_path);
                }
                $data['archivo_protocolo_path'] = $data['archivo_protocolo']->store('transferencia/protocolos', 'private');
                unset($data['archivo_protocolo']);
            } elseif (isset($data['tiene_protocolo']) && $data['tiene_protocolo'] === false) {
                if ($ensayo->archivo_protocolo_path && Storage::disk('private')->exists($ensayo->archivo_protocolo_path)) {
                    Storage::disk('private')->delete($ensayo->archivo_protocolo_path);
                }
                $data['archivo_protocolo_path'] = null;
            }

            // Lógica Informe
            if (isset($data['archivo_informe']) && $data['archivo_informe'] instanceof \Illuminate\Http\UploadedFile) {
                if ($ensayo->archivo_informe_path && Storage::disk('private')->exists($ensayo->archivo_informe_path)) {
                    Storage::disk('private')->delete($ensayo->archivo_informe_path);
                }
                $data['archivo_informe_path'] = $data['archivo_informe']->store('transferencia/informes', 'private');
                unset($data['archivo_informe']);
            }

            $ensayo->update($data);

            if (isset($data['equipo_tecnico_ids'])) {
                $ensayo->equipoTecnico()->sync($data['equipo_tecnico_ids']);
            }

            return $ensayo->load(['equipoTecnico']);
        });
    }

    public function delete(Ensayo $ensayo): bool
    {
        return $ensayo->delete();
    }
}
