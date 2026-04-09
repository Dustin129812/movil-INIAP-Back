<?php

namespace Modules\Investigacion\Transformers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Investigacion\Http\Resources\ActivityResource;

class ReusableActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activityType' => $this->activity_type,
            'name' => $this->name,
            'description' => $this->description,
            'workLocation' => $this->work_location,
            'observations' => $this->observations,

            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'materials' => MaterialResource::collection($this->whenLoaded('materials')),
            'performanceIndicators' => PerformanceIndicatorResource::collection($this->whenLoaded('performanceIndicators')),
            'logisticSupportUsers' => UserResource::collection($this->whenLoaded('logisticSupportUsers')),
        ];
    }
}
