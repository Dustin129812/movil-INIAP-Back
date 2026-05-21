<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Traits\ScopesByLocation;

class EnsayoService
{
    use ScopesByLocation;

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Ensayo::query()->with(['equipoTecnico', 'producto', 'actividad', 'user:id,name']);

        $query = $this->applyLocationScope($query);

        $canSeeAll = $filters['can_see_all'] ?? false;
        if (!$canSeeAll && !empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id'])
                    ->orWhereHas('equipoTecnico', function ($teamQuery) use ($filters) {
                        $teamQuery->where('users.id', $filters['user_id']);
                    });
            });
        }

        if (!empty($filters['filter_user_id'])) {
            $query->where('user_id', $filters['filter_user_id']);
        }

        if ($canSeeAll && !empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['provincia_id']) || !empty($filters['canton_id']) || !empty($filters['parroquia_id'])) {
            $query->whereHas('parcelas', function ($q) use ($filters) {
                if (!empty($filters['provincia_id'])) { $q->where('provincia_id', $filters['provincia_id']); }
                if (!empty($filters['canton_id'])) { $q->where('canton_id', $filters['canton_id']); }
                if (!empty($filters['parroquia_id'])) { $q->where('parroquia_id', $filters['parroquia_id']); }
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                    ->orWhere('nombre_tecnologia', 'ilike', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['estado'])) { $query->where('estado', $filters['estado']); }

        $perPage = $filters['per_page'] ?? 100;
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

            $data['location_id'] = request()->user()->location_id;

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
        return DB::transaction(function () use ($ensayo) {
            return $ensayo->delete();
        });
    }
}
