<?php

namespace Modules\AgroDecide\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->name,
            'canton_id'   => $this->canton_id,
            'province_id' => $this->province_id,
        ];
    }
}
