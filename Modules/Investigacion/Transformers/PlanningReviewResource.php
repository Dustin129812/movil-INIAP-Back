<?php

namespace Modules\Investigacion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanningReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        $activity = $this->whenLoaded('activity');
        $product = $activity ? $activity->product : null;
        $group = $this->assigned_group;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'date' => $this->date,

            'user_id' => $this->display_user_id ?? $this->user_id,
            'user_name' => $this->display_user_name ?? $this->user?->name,
            'is_owner' => $this->is_owner ?? true,
            'owner_name' => $this->ownerName ?? $this->user?->name,

            'description' => $this->description ?? $this->observation,
            'observation' => $this->description ?? $this->observation,

            'activity_name' => $activity ? $activity->description : null,
            'activity_type' => $this->activity_type,

            'product_id' => $product ? $product->id : null,
            'product_name' => $product ? $product->name : null,

            'group_id' => $group ? $group->id : -1,
            'group_name' => $group ? $group->name : 'Sin Grupo Asignado',
        ];
    }
}
