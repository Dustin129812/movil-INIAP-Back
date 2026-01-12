<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventario\Entities\Machinery;

class ActivityMachinery extends Model
{
    protected $table = 'p_activity_machinery';
    protected $guarded = ['id'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // Relación con el Activo Fijo (Para saber nombre: Camioneta, Tractor)
    public function machine()
    {
        return $this->belongsTo(Machinery::class, 'machinery_id');
    }
}
