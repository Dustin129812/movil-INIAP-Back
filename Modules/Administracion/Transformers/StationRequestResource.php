<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class StationRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $dispatch = $this->dispatch;
        $status = $dispatch ? $dispatch->status : 'pending';

        $logisticItem = $this->materials->firstWhere('pivot.request_type', 'logistics');

        $mobilizationData = [];
        if ($logisticItem) {
            $metadata = $logisticItem->pivot->metadata;
            $decodedMeta = is_string($metadata) ? json_decode($metadata, true) : ($metadata ?? []);

            $mobilizationData = [
                'type' => $decodedMeta['tipo'] ?? 'interna',
                'destination' => $decodedMeta['lugar'] ?? $this->work_location,
                'departure_time' => $decodedMeta['fecha_desde'] ?? 'Por definir',
                'return_time' => $decodedMeta['fecha_hasta'] ?? 'Por definir',
                'justification' => $logisticItem->pivot->description ?? $this->description,
                'passengers' => $logisticItem->pivot->quantity ?? 1,
            ];
        }

        return [
            'id' => $this->id,
            'date' => $this->date,
            'technician_name' => $this->user->name ?? 'Técnico',
            'activity_description' => $this->description,
            'status' => $status,
            'mobilization' => $mobilizationData,
            'requested_items' => $this->materials->map(function ($material) {
                return [
                    'material_id' => $material->id,
                    'name' => $material->name,
                    'requested_qty' => $material->pivot->quantity ?? 0,
                    'description' => $material->pivot->description ?? '',
                ];
            })->toArray(),
            'admin_notes' => $dispatch ? $dispatch->admin_notes : null,
        ];
    }
}
