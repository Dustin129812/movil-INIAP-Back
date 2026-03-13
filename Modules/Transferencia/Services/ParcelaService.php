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

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%')
                ->orWhere('localidad', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['ensayo_id'])) {
            $query->where('ensayo_id', $filters['ensayo_id']);
        }

        if (!empty($filters['provincia_id'])) {
            $query->where('provincia_id', $filters['provincia_id']);
        }

        if (!empty($filters['canton_id'])) {
            $query->where('canton_id', $filters['canton_id']);
        }

        if (!empty($filters['parroquia_id'])) {
            $query->where('parroquia_id', $filters['parroquia_id']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Parcela
    {
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
