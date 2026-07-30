<?php

namespace Modules\AgroDecide\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class HojaDatoResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->uuid_movil,
            'plantilla' => $this->nombre_plantilla,
            'variables' => $this->datos_variables,
        ];
    }
}
