<?php

namespace Modules\Kopia\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class VariedadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'caracteristicas' => $this->caracteristicas_base ?? (object)[],
        ];
    }
}
