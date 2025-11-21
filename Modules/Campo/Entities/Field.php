<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'p_fields';
    protected $fillable = ['name', 'area_hectares', 'current_crop', 'is_active'];

    // Relación: Un lote tiene muchas actividades a lo largo del tiempo
    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('activity_date', 'desc');
    }
}
