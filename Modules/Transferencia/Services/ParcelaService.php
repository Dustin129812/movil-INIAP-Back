<?php

namespace Modules\Transferencia\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Parcela;

class ParcelaService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Parcela::query()
            ->with(['ensayo', 'organizacion', 'provincia', 'canton', 'parroquia']);

        $user = request()->user();

        // Aislamiento de datos por ubicación
        if ($user && !$user->hasRole('administrador')) {
            $query->where('location_id', $user->location_id);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                    ->orWhere('localidad', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['estado'])) { $query->where('estado', $filters['estado']); }
        if (!empty($filters['ensayo_id'])) { $query->where('ensayo_id', $filters['ensayo_id']); }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Parcela
    {
        $data['location_id'] = request()->user()->location_id;

        $parcela = Parcela::create($data);

        return $parcela->load(['ensayo', 'organizacion', 'provincia', 'canton']);
    }

    public function update(Parcela $parcela, array $data): Parcela
    {
        $parcela->update($data);

        return $parcela->load(['ensayo', 'organizacion', 'provincia', 'canton']);
    }

    public function delete(Parcela $parcela): bool
    {
        return $parcela->delete();
    }
}
