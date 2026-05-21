<?php

namespace Modules\Transferencia\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Transferencia\Entities\Acuerdo;
use Modules\Transferencia\Traits\ScopesByLocation;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class AcuerdoService
{
    use ScopesByLocation;

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Acuerdo::query()
            ->with(['organizacion'])
            ->withCount('parcelas');

        $query = $this->applyLocationScope($query);

        if (!empty($filters['search'])) {
            $query->whereHas('organizacion', function ($q) use ($filters) {
                $q->where('nombre', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['provincia_id']) || !empty($filters['canton_id']) || !empty($filters['parroquia_id'])) {
            $query->whereHas('parcelas', function ($q) use ($filters) {
                if (!empty($filters['provincia_id'])) { $q->where('provincia_id', $filters['provincia_id']); }
                if (!empty($filters['canton_id'])) { $q->where('canton_id', $filters['canton_id']); }
                if (!empty($filters['parroquia_id'])) { $q->where('parroquia_id', $filters['parroquia_id']); }
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $perPage = $filters['per_page'] ?? 100;
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Acuerdo
    {
        return DB::transaction(function () use ($data) {
            $data['location_id'] = request()->user()->location_id;

            if (isset($data['archivo_acuerdo']) && $data['archivo_acuerdo'] instanceof \Illuminate\Http\UploadedFile) {
                $data['archivo_acuerdo_path'] = $data['archivo_acuerdo']->store('transferencia/acuerdos', 'private');
                unset($data['archivo_acuerdo']);
            }

            return Acuerdo::create($data)->load('organizacion');
        });
    }

    public function update(Acuerdo $acuerdo, array $data): Acuerdo
    {
        return DB::transaction(function () use ($acuerdo, $data) {
            if (isset($data['archivo_acuerdo']) && $data['archivo_acuerdo'] instanceof \Illuminate\Http\UploadedFile) {

                if ($acuerdo->archivo_acuerdo_path && Storage::disk('private')->exists($acuerdo->archivo_acuerdo_path)) {
                    Storage::disk('private')->delete($acuerdo->archivo_acuerdo_path);
                }

                $data['archivo_acuerdo_path'] = $data['archivo_acuerdo']->store('transferencia/acuerdos', 'private');
                unset($data['archivo_acuerdo']);
            }

            $acuerdo->update($data);

            return $acuerdo->load('organizacion');
        });
    }

    public function delete(Acuerdo $acuerdo): bool
    {
        return DB::transaction(function () use ($acuerdo) {
            if ($acuerdo->archivo_acuerdo_path && Storage::disk('private')->exists($acuerdo->archivo_acuerdo_path)) {
                Storage::disk('private')->delete($acuerdo->archivo_acuerdo_path);
            }

            return $acuerdo->delete();
        });
    }

    /**
     * Procesa la descarga física del archivo desde el almacenamiento privado.
     */
    public function downloadFile(Acuerdo $acuerdo): StreamedResponse
    {
        if (!$acuerdo->archivo_acuerdo_path || !Storage::disk('private')->exists($acuerdo->archivo_acuerdo_path)) {
            abort(404, 'El documento solicitado no se encuentra disponible o fue removido.');
        }

        $extension = pathinfo($acuerdo->archivo_acuerdo_path, PATHINFO_EXTENSION);
        $slugNombre = Str::slug($acuerdo->organizacion->nombre ?? 'organizacion');
        $nombreDescarga = "Acuerdo_{$slugNombre}_{$acuerdo->id}.{$extension}";

        return Storage::disk('private')->response(
            $acuerdo->archivo_acuerdo_path,
            $nombreDescarga,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nombreDescarga . '"'
            ]
        );
    }
}
