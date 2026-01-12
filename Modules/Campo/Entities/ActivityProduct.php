<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventario\Entities\Product;

class ActivityProduct extends Model
{
    protected $table = 'p_activity_products';
    protected $guarded = ['id'];

    // Relación inversa: Pertenece a una actividad
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // Relación con el Inventario (Para saber nombre, unidad, etc.)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
