<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo JSON limpio.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate' => $this->plate,
            'brand' => $this->brand,
            'model' => $this->model,
            'is_active' => (bool) $this->is_active,
            'display_label' => trim("{$this->plate} " . ($this->brand ? "({$this->brand})" : '')),
        ];
    }
}
