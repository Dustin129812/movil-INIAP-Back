<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Campo\Entities\Activity;

class ProductionBatch extends Model
{
    protected $table = 'prod_batches';
    protected $guarded = [];

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
        // Agregamos un chequeo de seguridad por si el protocolo fue borrado
        return $this->protocol ? $this->protocol->variety : null;
    }
}
