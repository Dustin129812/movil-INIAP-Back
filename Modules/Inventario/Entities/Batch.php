<?php

namespace Modules\Inventario\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Batch extends Model
{
    protected $table = 'inv_batches';
    protected $fillable = [
        'product_id', 'batch_code', 'expiration_date',
        'unit_cost', 'initial_quantity', 'current_quantity',
        'is_active', 'is_expired'
    ];

    protected $casts = [
        'expiration_date' => 'datetime:Y-m-d',
        'is_active' => 'boolean',
        'is_expired' => 'boolean',
        'unit_cost' => 'decimal:4',
        'current_quantity' => 'decimal:2'
    ];
    public function getStatusAttribute()
    {
        if (!$this->expiration_date) return 'OK';

        $now = Carbon::now();

        if ($now->gt($this->expiration_date)) {
            return 'EXPIRED'; // [cite: 41]
        }

        // Alerta si caduca en menos de 30 días
        if ($now->diffInDays($this->expiration_date, false) <= 30) {
            return 'WARNING';
        }

        return 'OK';
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
