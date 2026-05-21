<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Transferencia\Entities\Parcela;
use Modules\Transferencia\Traits\ScopesByLocation;

class ParcelaService
{
    use ScopesByLocation;

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Parcela::query()->with(['ensayo', 'organizacion', 'provincia', 'canton', 'parroquia']);

        $query = $this->applyLocationScope($query);

        $canSeeAll = $filters['can_see_all'] ?? false;

        if (!$canSeeAll && !empty($filters['user_id'])) {
            $query->whereHas('ensayo.equipoTecnico', function ($teamQuery) use ($filters) {
                $teamQuery->where('users.id', $filters['user_id']);
            });
        }

        if ($canSeeAll && !empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['provincia_id'])) { $query->where('provincia_id', $filters['provincia_id']); }
        if (!empty($filters['canton_id'])) { $query->where('canton_id', $filters['canton_id']); }
        if (!empty($filters['parroquia_id'])) { $query->where('parroquia_id', $filters['parroquia_id']); }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                    ->orWhere('localidad', 'ilike', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['estado'])) { $query->where('estado', $filters['estado']); }

        $perPage = $filters['per_page'] ?? 100;
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Parcela
    {
        return DB::transaction(function () use ($data) {
            $data['location_id'] = request()->user()->location_id;
            $parcela = Parcela::create($data);
            return $parcela->load(['ensayo', 'organizacion', 'provincia', 'canton']);
        });
    }

    public function update(Parcela $parcela, array $data): Parcela
    {
        return DB::transaction(function () use ($parcela, $data) {
            $parcela->update($data);
            return $parcela->load(['ensayo', 'organizacion', 'provincia', 'canton']);
        });
    }

    public function delete(Parcela $parcela): bool
    {
        return DB::transaction(function () use ($parcela) {
            return $parcela->delete();
        });
    }
}
