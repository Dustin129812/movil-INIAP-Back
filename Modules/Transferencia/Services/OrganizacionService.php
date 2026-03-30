<?php

namespace Modules\Transferencia\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Organizacion;

class OrganizacionService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Organizacion::query()
            ->with(['provincia', 'canton', 'parroquia']);

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo_organizacion', $filters['tipo']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Organizacion
    {
        return Organizacion::create($data)->load(['provincia', 'canton', 'parroquia']);
    }

    public function update(Organizacion $organizacion, array $data): Organizacion
    {
        $organizacion->update($data);

        return $organizacion->load(['provincia', 'canton', 'parroquia']);
    }

    public function delete(Organizacion $organizacion): bool
    {
        return $organizacion->delete();
    }
}
