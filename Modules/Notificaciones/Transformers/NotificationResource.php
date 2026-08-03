<?php

namespace Modules\Notificaciones\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['type'] ?? 'general',
            'title' => $this->data['title'] ?? 'Sin título',
            'preview' => $this->data['body_preview'] ?? '',
            'content' => $this->data['full_body'] ?? '',
            'action_url' => $this->data['action_url'] ?? null,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
