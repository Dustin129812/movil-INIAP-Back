<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class FleetDispatchResource extends JsonResource
{
    public function toArray($request): array
    {
        $mob = $this->mobilization;
        if (is_string($mob)) {
            $mob = json_decode($mob, true);
        } else {
            $mob = (array) $mob;
        }

        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'status' => $this->status,
            'technician_name' => $this->user ? $this->user->name : 'Técnico',
            'mobilization' => [
                'destination' => $mob['destination'] ?? 'NO ESPECIFICADO',
                'passengers' => isset($mob['passengers']) ? (int) $mob['passengers'] : 1,
            ],
        ];
    }
}
