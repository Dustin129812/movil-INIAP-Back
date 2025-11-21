<?php

namespace Modules\Inventario\Entities;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'inv_products';
    protected $fillable = [
        'category_id',
        'name',
        'scientific_name',
        'active_ingredient',
        'unit',
        'min_stock',
        'requires_batch_control'
    ];
    public function batches() {
        return $this->hasMany(Batch::class);
    }

    // Total de stock sumando todos los lotes activos
    public function getTotalStockAttribute() {
        return $this->batches()->where('is_active', true)->sum('current_quantity');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
