<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'indicator_id' => $this->indicator_id,
            'indicator_name' => $this->indicator ? $this->indicator->name : null,
        ];
    }
}
