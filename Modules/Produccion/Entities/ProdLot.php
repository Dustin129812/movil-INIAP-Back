<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ProdLot extends Model
{
    protected $table = 'lots';
    protected $fillable = [
        'name',
        'surface',
        'location'
    ];

    public function productionPlans()
    {
        return $this->hasMany(ProductionPlan::class);
    }
}
