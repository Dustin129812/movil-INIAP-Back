<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Investigacion\Entities\Rubro;

class AdministrativeUnit extends Model
{
    protected $table = 'th_administrative_units';
    protected $fillable = ['name'];

    public function visibleRubros()
    {
        return $this->belongsToMany(
            Rubro::class,
            'admin_poa_visibility',
            'th_administrative_unit_id',
            'rubro_id'
        );
    }
}


