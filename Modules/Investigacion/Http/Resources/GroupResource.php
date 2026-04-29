<?php

namespace Modules\Investigacion\Http\Resources;

use App\Http\Resources\UserBasicResource; // <- Importamos el básico
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rubro' => $this->whenLoaded('rubro', fn () => [
                'id' => $this->rubro->id,
                'name' => $this->rubro->name,
            ]),
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ]),
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
            ]),
            'creator' => new UserBasicResource($this->whenLoaded('creator')),
            'responsible' => new UserBasicResource($this->whenLoaded('responsible')),
            'members_count' => $this->whenCounted('members'),
            'members' => UserBasicResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
