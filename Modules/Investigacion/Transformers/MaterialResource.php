<?php

namespace Modules\Investigacion\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => $this->when($this->pivot, function () {
                return $this->pivot->quantity;
            }),
            'pivotDescription' => $this->when($this->pivot, function () {
                return $this->pivot->description;
            }),
        ];
    }
}
