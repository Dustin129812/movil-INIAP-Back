<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DispatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'items' => [
                'requested' => $this->requested_items,
                'dispatched' => $this->dispatched_items,
            ],
            'notes' => $this->admin_notes,
            'processed_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'admin' => $this->admin ? $this->admin->name : null,
        ];
    }
}
