<?php

namespace Modules\Administracion\Services;

use Modules\Administracion\Entities\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryItemService
{
    /**
     * Registra un nuevo ítem en el catálogo maestro.
     * * @param array $data Datos validados desde el FormRequest
     * @return InventoryItem
     */
    public function createItem(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {

            // Aquí podríamos agregar lógica para autogenerar un SKU si $data['sku'] es null

            return InventoryItem::create([
                'type' => $data['type'],
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'attributes' => $data['attributes'],
                'is_active' => true,
            ]);
        });
    }

    /**
     * Actualiza un ítem existente en el catálogo.
     */
    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->update([
                'type' => $data['type'] ?? $item->type,
                'name' => $data['name'] ?? $item->name,
                'sku' => $data['sku'] ?? $item->sku,
                'attributes' => $data['attributes'] ?? $item->attributes,
                'is_active' => $data['is_active'] ?? $item->is_active,
            ]);

            return $item;
        });
    }

    /**
     * Elimina un ítem del catálogo (Hard Delete) si no tiene dependencias.
     * Si las tiene, se recomienda cambiar 'is_active' a false desde el update.
     */
    public function deleteItem(InventoryItem $item): bool
    {
        if ($item->warehouses()->exists()) {
            throw new \Exception('No se puede eliminar este ítem porque tiene stock registrado en bodegas. Inactívelo en su lugar.');
        }

        return $item->delete();
    }
}
