<?php

namespace Modules\Administracion\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Administracion\Entities\Vehicle;

class LogisticsCatalogService
{
    /**
     * Retorna vehículos de una ubicación.
     * Se ignorará el estado 'is_available' para permitir programación múltiple en la semana.
     */
    public function getVehiclesByLocation(int $locationId, bool $includeInactive = false): Collection
    {
        return Vehicle::where('location_id', $locationId)
            ->when(!$includeInactive, function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('plate', 'asc')
            ->get();
    }

    public function getDriversByLocation(int $locationId): Collection
    {
        return User::where('location_id', $locationId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'email']);
    }
}
