<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location_id' => $this->location_id,
            'is_active' => $this->is_active,

            'responsible' => [
                'id' => $this->responsible_id,
                'name' => $this->whenLoaded('responsible', fn() => $this->responsible->name),
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
