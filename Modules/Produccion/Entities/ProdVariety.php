<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use app\Models\Crops;

class ProdVariety extends Model
{
    protected $fillable = [
        'productive_rubro_id',
        'crop_id',
        'name',
        'category_id',
        'variety_type_id'
    ];

    public function crop()
    {
        return $this->belongsTo(Crops::class); // Asumiendo que tienes el modelo Crop
    }

    public function category()
    {
        return $this->belongsTo(ProdCategory::class);
    }

    public function type()
    {
        return $this->belongsTo(ProdVarietyType::class, 'variety_type_id');
    }
}
