<?php

namespace Modules\Administracion\Services;

use App\Models\User;
use Modules\Administracion\Entities\Vehicle;

class VehicleManagementService
{
    /**
     * Registra un nuevo vehículo amarrado a la estación del administrador.
     */
    public function storeVehicle(array $data, User $admin): Vehicle
    {
        $data['location_id'] = $admin->location_id;
        $data['is_active'] = true;

        return Vehicle::create($data);
    }

    /**
     * Alterna el estado (Activo/Inactivo) de un vehículo.
     * Solo permite modificar vehículos de su propia estación.
     */
    public function toggleVehicleStatus(int $id, User $admin): Vehicle
    {
        $vehicle = Vehicle::where('location_id', $admin->location_id)->findOrFail($id);

        $vehicle->is_active = !$vehicle->is_active;
        $vehicle->save();

        return $vehicle;
    }
}
