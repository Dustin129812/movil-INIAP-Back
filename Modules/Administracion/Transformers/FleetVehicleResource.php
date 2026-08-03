<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class FleetVehicleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plate' => $this->plate,
            'brand' => $this->brand,
            'model' => $this->model,
            'is_available' => (bool) $this->is_available,
        ];
    }
}
