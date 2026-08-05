<?php

namespace Modules\AgroDecide\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAgroDecideResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'correo_institucional' => $this->correo_institucional,
            'nombre' => $this->nombre,
            'estado' => $this->estado,
        ];
    }
}
