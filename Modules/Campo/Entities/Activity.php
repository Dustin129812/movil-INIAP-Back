<?php

namespace Modules\Campo\Entities;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'p_activities';

    // Permitimos asignar todo en masa para facilitar el guardado
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'activity_date' => 'date:Y-m-d',
        'labor_cost_total' => 'decimal:2',
        'extra_data' => 'array',
    ];

    // 1. Pertenece a un Lote
    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    // 2. Tiene muchos productos consumidos (Insumos)
    public function products()
    {
        return $this->hasMany(ActivityProduct::class);
    }

    // 3. Tiene mucha maquinaria usada
    public function machinery()
    {
        return $this->hasMany(ActivityMachinery::class);
    }

    // Un pequeño helper para calcular el costo total absoluto de la labor
    public function getTotalCostAttribute()
    {
        $insumos = $this->products->sum('total_cost');
        $maquina = $this->machinery->sum('total_cost');
        $manoObra = $this->labor_cost_total;

        return $insumos + $maquina + $manoObra;
    }
}
