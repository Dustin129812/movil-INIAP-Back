<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $fillable = [
        'variety_id',
        'lot_id',
        'seed_quantity',
        'seed_category_id',
        'expected_quantity',
        'unit_of_measure',
        'expense_type',
        'observation'
    ];

    public function variety()
    {
        return $this->belongsTo(ProdVariety::class);
    }

    public function lot()
    {
        return $this->belongsTo(ProdLot::class);
    }

    public function seedCategory()
    {
        return $this->belongsTo(ProdCategory::class, 'seed_category_id');
    }
}
