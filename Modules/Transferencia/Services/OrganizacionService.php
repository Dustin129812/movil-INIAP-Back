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

        $canSeeAll = $filters['can_see_all'] ?? false;

        if (!$canSeeAll) {
            $query = $this->applyLocationScope($query);
        } elseif (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('nombre', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo_organizacion', $filters['tipo']);
        }

        if (isset($filters['huerfanos_only']) && $filters['huerfanos_only'] === 'true') {
            $query->whereNull('user_id');
        } elseif (!empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id'])
                    ->orWhereNull('user_id');
            });
        }

        if (!empty($filters['provincia_id'])) { $query->where('provincia_id', $filters['provincia_id']); }
        if (!empty($filters['canton_id'])) { $query->where('canton_id', $filters['canton_id']); }
        if (!empty($filters['parroquia_id'])) { $query->where('parroquia_id', $filters['parroquia_id']); }

        $perPage = $filters['per_page'] ?? 100;

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Organizacion
    {
        return DB::transaction(function () use ($data) {
            $data['location_id'] = request()->user()->location_id;
            $data['user_id'] = request()->user()->id;

            return Organizacion::create($data)->load(['provincia', 'canton', 'parroquia']);
        });
    }

    public function update(Organizacion $organizacion, array $data): Organizacion
    {
        DB::transaction(function () use ($organizacion, $data) {
            if (is_null($organizacion->user_id)) {
                $data['user_id'] = request()->user()->id;
            }

            $organizacion->fill($data)->save();
        });

        return $organizacion->refresh()->load(['provincia', 'canton', 'parroquia']);
    }

    public function delete(Organizacion $organizacion): bool
    {
        return DB::transaction(function () use ($organizacion) {
            return $organizacion->delete();
        });
    }

    public function claim(Organizacion $organizacion): Organizacion
    {
        return DB::transaction(function () use ($organizacion) {
            if (!is_null($organizacion->user_id)) {
                abort(422, 'Esta organización ya cuenta con un responsable técnico asignado.');
            }

            $organizacion->update([
                'user_id' => request()->user()->id
            ]);

            return $organizacion->load(['provincia', 'canton', 'parroquia']);
        });
    }
}
