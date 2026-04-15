<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Traits\ScopesByLocation;

class OrganizacionService
{
    use ScopesByLocation;

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Organizacion::query()
            ->with(['provincia', 'canton', 'parroquia']);

        // Aplicamos el Trait
        $query = $this->applyLocationScope($query);

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo_organizacion', $filters['tipo']);
        }

        $perPage = $filters['per_page'] ?? 100;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Organizacion
    {
        return DB::transaction(function () use ($data) {
            $data['location_id'] = request()->user()->location_id;
            return Organizacion::create($data)->load(['provincia', 'canton', 'parroquia']);
        });
    }

    public function update(Organizacion $organizacion, array $data): Organizacion
    {
        return DB::transaction(function () use ($organizacion, $data) {
            $organizacion->update($data);
            return $organizacion->load(['provincia', 'canton', 'parroquia']);
        });
    }

    public function delete(Organizacion $organizacion): bool
    {
        return DB::transaction(function () use ($organizacion) {
            return $organizacion->delete();
        });
    }
}
