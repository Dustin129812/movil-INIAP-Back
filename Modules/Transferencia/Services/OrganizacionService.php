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

        $user = request()->user();

        // Aislamiento por ubicación
        if ($user && !$user->hasRole('administrador')) {
            $query->where('location_id', $user->location_id);
        }

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
        $data['location_id'] = request()->user()->location_id;

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
