<?php

namespace Modules\Administracion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    protected $table = 'administracion.warehouses';

    protected $fillable = [
        'name',
        'location_id',    // Estación Experimental
        'responsible_id', // Usuario encargado
        'is_active'
    ];

    /**
     * El responsable de la custodia de esta bodega.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Ítems del catálogo que tienen stock en esta bodega.
     */
    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'administracion.warehouse_items')
            ->withPivot('stock', 'min_stock')
            ->withTimestamps();
    }
}
