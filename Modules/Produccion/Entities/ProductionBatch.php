<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Campo\Entities\Activity;

class ProductionBatch extends Model
{
    protected $table = 'prod_batches';
    protected $guarded = [];

    public function activities()
    {
        return $this->hasMany(Activity::class, 'prod_batch_id');
    }

    public function getRealCostAttribute()
    {
        return $this->activities->get()->sum('total_cost');
    }

    public function getUnitCostAttribute()
    {
        if ($this->current_quantity <= 0) return 0;
        return $this->real_cost / $this->current_quantity;
    }
    public function protocol()
    {
        return $this->belongsTo(ProdProtocol::class);
    }

    // Helper para saber qué variedad es
    public function getVarietyAttribute()
    {
        return $this->protocol->variety;
    }
}
