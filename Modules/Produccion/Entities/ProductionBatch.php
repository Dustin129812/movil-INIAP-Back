<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Campo\Entities\Activity;
use Modules\Campo\Entities\Field;

class ProductionBatch extends Model
{
    protected $table = 'prod_batches';
    protected $fillable = [
        'batch_code',
        'protocol_id',
        'field_id',
        'environment',
        'start_date',
        'estimated_end_date',
        'initial_quantity',
        'current_quantity',
        'status',
        'current_stage'
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class, 'prod_batch_id');
    }

    public function getRealCostAttribute()
    {
        // CORREGIDO: Se quitó "->get()" porque $this->activities ya es la colección de datos
        return $this->activities->sum('total_cost');
    }

    public function getUnitCostAttribute()
    {
        // Aseguramos que sea numérico para evitar errores si es null
        $qty = (float) $this->current_quantity;

        if ($qty <= 0) return 0;

        // Usamos el atributo calculado arriba
        return $this->real_cost / $qty;
    }

    public function protocol()
    {
        return $this->belongsTo(ProdProtocol::class, 'protocol_id'); // Asegura que la FK sea correcta (usualmente protocol_id)
    }

    // Helper para saber qué variedad es
    public function getVarietyAttribute()
    {
        return $this->protocol ? $this->protocol->variety : null;
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
