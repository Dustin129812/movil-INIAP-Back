<?php

namespace Modules\Investigacion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'budget' => $this->budget,
            'ponderacion' => $this->ponderacion,
            'user' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? 'Sin nombre',
                    ];
                })->toArray();
            }, []),
            'indicator_id' => $this->indicator_id,
            'indicator_name' => $this->whenLoaded('indicator', fn() => $this->indicator?->name, null),
            'monthly_distribution' => $this->monthly_distribution ?? [],
        ];
    }
}
