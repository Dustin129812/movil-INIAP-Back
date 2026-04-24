<?php

namespace Modules\Administracion\Services;

use Modules\Administracion\Entities\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Crea una nueva bodega física o virtual.
     */
    public function createWarehouse(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            return Warehouse::create([
                'name' => $data['name'],
                'location_id' => $data['location_id'],
                'responsible_id' => $data['responsible_id'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Elimina una bodega solo si su stock está en cero.
     */
    public function deleteWarehouse(Warehouse $warehouse): bool
    {
        // Regla de Negocio: Evitar borrar bodegas con inventario activo
        $hasStock = $warehouse->inventoryItems()->wherePivot('stock', '>', 0)->exists();

        if ($hasStock) {
            throw new \Exception('Operación denegada: La bodega aún tiene ítems con stock. Transfiera los materiales primero.');
        }

        return $warehouse->delete();
    }
}
