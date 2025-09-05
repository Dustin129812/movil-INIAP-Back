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
            'responsible' => new UserResource($this->whenLoaded('responsible')),
            'members_count' => $this->whenCounted('members'),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
