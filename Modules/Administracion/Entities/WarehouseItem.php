<?php

namespace Modules\Administracion\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WarehouseItem extends Pivot
{
    protected $table = 'administracion.warehouse_items';

    public $incrementing = true;

    protected $fillable = [
        'warehouse_id',
        'inventory_item_id',
        'stock',
        'min_stock'
    ];
}
