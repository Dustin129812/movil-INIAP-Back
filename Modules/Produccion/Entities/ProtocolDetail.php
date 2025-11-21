<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventario\Entities\Product;   // Importamos del Modulo Inventario
use Modules\Inventario\Entities\Machinery; // Importamos del Modulo Inventario

class ProtocolDetail extends Model
{
    protected $table = 'prod_protocol_details';
    protected $guarded = [];

    // Relación con el Inventario
    public function inventoryProduct()
    {
        return $this->belongsTo(Product::class, 'inv_product_id');
    }

    // Relación con la Maquinaria
    public function inventoryMachinery()
    {
        return $this->belongsTo(Machinery::class, 'inv_machinery_id');
    }

    public function protocol()
    {
        return $this->belongsTo(ProdProtocol::class);
    }
}
