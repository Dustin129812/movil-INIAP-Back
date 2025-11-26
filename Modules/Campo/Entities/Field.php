<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'p_fields';
    protected $fillable =
        [
            'name',
            'type',
            'area_hectares',
            'current_crop',
            'is_active'
        ];

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('activity_date', 'desc');
    }
}
