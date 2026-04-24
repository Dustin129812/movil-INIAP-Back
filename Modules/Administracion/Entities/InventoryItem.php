<?php

namespace Modules\Administracion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryItem extends Model
{
    protected $table = 'administracion.inventory_items';

    protected $fillable = [
        'type',        // insumo, vehiculo, equipo
        'name',
        'sku',
        'attributes',  // Datos dinámicos
        'is_active'
    ];

    protected $casts = [
        'attributes' => AsArrayObject::class,
        'is_active' => 'boolean',
    ];

    /**
     * Relación con las bodegas donde existe este ítem.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'administracion.warehouse_items')
            ->withPivot('stock', 'min_stock')
            ->withTimestamps();
    }
}
