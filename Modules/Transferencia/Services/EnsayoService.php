<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Traits\ScopesByLocation;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class EnsayoService
{
    use ScopesByLocation;

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Ensayo::query()->with(['equipoTecnico', 'producto', 'actividad']);

        $canSeeAll = $filters['can_see_all'] ?? false;

        if (!$canSeeAll) {
            $query = $this->applyLocationScope($query);
        } elseif (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['huerfanos_only']) && $filters['huerfanos_only'] === 'true') {
            $query->whereNull('user_id');
        } elseif (!$canSeeAll && !empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('equipoTecnico', function ($teamQuery) use ($filters) {
                    $teamQuery->where('users.id', $filters['user_id']);
                })->orWhereNull('user_id');
            });
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
            // 1. Manejo de Archivos
            if (isset($data['archivo_protocolo']) && $data['archivo_protocolo'] instanceof \Illuminate\Http\UploadedFile) {
                $data['archivo_protocolo_path'] = $data['archivo_protocolo']->store('transferencia/protocolos', 'private');
                unset($data['archivo_protocolo']);
            }
            if (isset($data['archivo_informe']) && $data['archivo_informe'] instanceof \Illuminate\Http\UploadedFile) {
                $data['archivo_informe_path'] = $data['archivo_informe']->store('transferencia/informes', 'private');
                unset($data['archivo_informe']);
            }

            // 2. Creación Base
            $data['location_id'] = request()->user()->location_id;
            $ensayo = Ensayo::create($data);

            // 3. TRADUCCIÓN LÓGICA: De Equipo a Usuarios
            // No tocamos la BD en producción, usamos la relación que ya existe
            if (!empty($data['equipo_id'])) {
                // Suponiendo que tu modelo Equipo tiene una relación 'users' o 'miembros'
                $userIds = Equipo::findOrFail($data['equipo_id'])
                    ->users()
                    ->pluck('users.id')
                    ->toArray();

                // 'equipoTecnico' en tu modelo Ensayo sigue siendo un belongsToMany a User
                $ensayo->equipoTecnico()->sync($userIds);
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

    /**
     * Procesa la descarga física del protocolo desde el almacenamiento privado.
     */
    public function downloadProtocolo(Ensayo $ensayo): StreamedResponse
    {
        if (!$ensayo->archivo_protocolo_path || !Storage::disk('private')->exists($ensayo->archivo_protocolo_path)) {
            abort(404, 'El documento solicitado no se encuentra disponible o fue removido.');
        }

        $extension = pathinfo($ensayo->archivo_protocolo_path, PATHINFO_EXTENSION);
        $slugNombre = Str::slug($ensayo->nombre ?? 'ensayo');
        $nombreDescarga = "Protocolo_{$slugNombre}_{$ensayo->id}.{$extension}";

        return Storage::disk('private')->response(
            $ensayo->archivo_protocolo_path,
            $nombreDescarga,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nombreDescarga . '"'
            ]
        );
    }

    public function claim(Ensayo $ensayo): Ensayo
    {
        return DB::transaction(function () use ($ensayo) {
            if (!is_null($ensayo->user_id)) {
                abort(422, 'Este ensayo científico ya cuenta con un investigador responsable.');
            }

            $ensayo->update([
                'user_id' => request()->user()->id
            ]);

            return $ensayo->load(['equipoTecnico']);
        });
    }
}
