<?php

// app/Http/Resources/GroupResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Cargar relaciones solo cuando estén disponibles para evitar N+1
            'rubro' => $this->whenLoaded('rubro', function () {
                return [
                    'id' => $this->rubro->id,
                    'name' => $this->rubro->name,
                ];
            }),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                ];
            }),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'members_count' => $this->whenCounted('members'), // Para la vista de lista
            'members' => UserResource::collection($this->whenLoaded('members')), // Para la vista detallada
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
