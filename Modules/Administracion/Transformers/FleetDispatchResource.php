<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class FleetDispatchResource extends JsonResource
{
    public function toArray($request): array
    {
        $logisticItem = $this->weekActivity?->materials->firstWhere('pivot.request_type', 'logistics');

        $mob = [];
        if ($logisticItem) {
            $metadata = $logisticItem->pivot->metadata;
            $mob = is_string($metadata) ? json_decode($metadata, true) : ($metadata ?? []);
        }

        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'status' => $this->status,
            'technician_name' => $this->weekActivity?->user?->name ?? 'Técnico',
            'mobilization' => [
                'destination' => $mob['lugar'] ?? 'NO ESPECIFICADO',
                'passengers' => isset($logisticItem->pivot->quantity) ? (int) $logisticItem->pivot->quantity : 1,
            ],
            'week_activity'   => $this->whenLoaded('weekActivity'),
            'created_at'      => $this->created_at,
        ];
    }
}
