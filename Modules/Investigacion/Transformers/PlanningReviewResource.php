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
            'user_id' => $this->user_id,
            'display_user_id' => $this->display_user_id ?? $this->user_id,
            'user_name' => $this->display_user_name ?? $this->user?->name,
            'is_owner' => $this->is_owner ?? true,
            'owner_name' => $this->ownerName ?? $this->user?->name,

            'id' => $this->id,
            'status' => $this->status,
            'date' => $this->date,
            'description' => $this->description ?? $this->observation,

            'activity_name' => $activity ? $activity->description : null,

            'product_id' => $product ? $product->id : null,
            'product_name' => $product ? $product->name : null,

            'group_id' => $group ? $group->id : null,
            'group_name' => $group ? $group->name : null,
        ];
    }
}
