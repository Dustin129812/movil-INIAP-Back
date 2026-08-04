<?php

namespace Modules\Produccion\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KardexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_movimiento' => $this->tipo_movimiento,

            'cantidad' => round((float)$this->cantidad, 4),
            'costo_unitario' => round((float)$this->costo_unitario, 4),
            'costo_total' => round((float)$this->costo_total, 4),

            'saldo_cantidad' => round((float)$this->saldo_cantidad, 4),
            'costo_promedio' => round((float)$this->costo_promedio, 4),

            'documento_referencia' => $this->documento_referencia,
            'fecha_movimiento' => $this->created_at->format('Y-m-d H:i:s'),

            'bodega' => $this->whenLoaded('bodega', fn() => $this->bodega->nombre),
            'insumo' => $this->whenLoaded('insumo', fn() => $this->insumo->nombre),
        ];
    }
}
