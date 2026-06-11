<?php

namespace Modules\Transferencia\Services;

use Illuminate\Http\UploadedFile;
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
            $data['archivo_protocolo_path'] = $this->uploadFiles($data['archivos_protocolos'] ?? [], 'transferencia/protocolos');
            $data['archivo_informe_path'] = $this->uploadFiles($data['archivos_informes'] ?? [], 'transferencia/informes');

            unset($data['archivos_protocolos'], $data['archivos_informes']);

            // 2. Creación Base
            $data['location_id'] = request()->user()->location_id;
            $data['user_id'] = request()->user()->id;
            $ensayo = Ensayo::create($data);


            if (!empty($data['equipo_id'])) {
                $userIds = Equipo::findOrFail($data['equipo_id'])
                    ->users()
                    ->pluck('users.id')
                    ->toArray();

                $ensayo->equipoTecnico()->sync($userIds);
            }

            return $ensayo->load(['equipoTecnico']);
        });
    }

    public function update(Ensayo $ensayo, array $data): Ensayo
    {
        return DB::transaction(function () use ($ensayo, $data) {
            $retainedProtocolos = $data['retained_protocolos'] ?? [];
            $this->cleanOrphanFiles($ensayo->archivo_protocolo_path ?? [], $retainedProtocolos);

            $newProtocolosPaths = $this->uploadFiles($data['archivos_protocolos'] ?? [], 'transferencia/protocolos');
            $data['archivo_protocolo_path'] = array_merge($retainedProtocolos, $newProtocolosPaths);

            $retainedInformes = $data['retained_informes'] ?? [];
            $this->cleanOrphanFiles($ensayo->archivo_informe_path ?? [], $retainedInformes);

            $newInformesPaths = $this->uploadFiles($data['archivos_informes'] ?? [], 'transferencia/informes');
            $data['archivo_informe_path'] = array_merge($retainedInformes, $newInformesPaths);

            unset($data['archivos_protocolos'], $data['archivos_informes'], $data['retained_protocolos'], $data['retained_informes']);

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
    public function downloadProtocolo(Ensayo $ensayo, int $index): StreamedResponse
    {
        $protocolos = $ensayo->archivo_protocolo_path ?? [];

        if (!isset($protocolos[$index]) || !Storage::disk('private')->exists($protocolos[$index])) {
            abort(404, 'El documento solicitado no se encuentra disponible o fue removido.');
        }

        $path = $protocolos[$index];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $slugNombre = Str::slug($ensayo->nombre ?? 'ensayo');
        $nombreDescarga = "Protocolo_{$slugNombre}_{$ensayo->id}_p{$index}.{$extension}";

        return Storage::disk('private')->response(
            $path,
            $nombreDescarga,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nombreDescarga . '"'
            ]
        );
    }

    private function uploadFiles(array $files, string $path): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $paths[] = $file->store($path, 'private');
            }
        }
        return $paths;
    }

    private function cleanOrphanFiles(array $existingPaths, array $retainedPaths): void
    {
        $toDelete = array_diff($existingPaths, $retainedPaths);
        foreach ($toDelete as $path) {
            if (Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }
        }
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
