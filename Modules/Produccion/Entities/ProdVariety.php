<?php

namespace Modules\Produccion\Entities;

use App\Models\Productive_Rubro; // Mayúscula inicial
use App\Models\Crops;            // Mayúscula inicial
use Illuminate\Database\Eloquent\Model;

class ProdVariety extends Model
{
    protected $table = 'varieties';

    protected $fillable = [
        'productive_rubro_id',
        'crop_id',
        'category_id',
        'variety_type_id',
        'name'
    ];

    public function productive_rubro()
    {
        return $this->belongsTo(Productive_Rubro::class, 'productive_rubro_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crops::class);
    }

    public function crops()
    {
        return $this->belongsTo(Crops::class);
    }

    public function category()
    {
        return $this->belongsTo(ProdCategory::class);
    }

    public function variety_type()
    {
        // Asegúrate de usar App con mayúscula como corregimos antes
        return $this->belongsTo(ProdVarietyType::class, 'variety_type_id');
    }
}
