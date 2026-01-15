<?php

namespace Modules\Investigacion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationNeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fill_date' => $this->fill_date,
            'station_name' => $this->whenLoaded('location', $this->location->name),
            'province_location' => $this->whenLoaded('location', fn() => $this->location->province->name),
            'canton_location' => $this->whenLoaded('location', fn() => $this->location->canton->name),
            'responsible_person' => $this->whenLoaded('user', $this->user->name),
            'responsible_position' => $this->whenLoaded('user', fn() => $this->user->position->name ?? 'N/A'),
            'expense_type' => $this->whenLoaded('expenseType', function () {
                if ($this->expenseType) {
                    return [
                        'id' => $this->expenseType->id,
                        'group' => $this->expenseType->group,
                        'name' => $this->expenseType->name,
                    ];
                }
                return null;
            }),
            'description' => $this->description,
            'estimated_amount' => $this->estimated_amount,
            'priority' => $this->priority,
            'expected_impact' => $this->expected_impact,
            'impact_type' => $this->impact_type,
            'problem_to_solve' => $this->problem_to_solve,
            'investment_risk' => $this->investment_risk,
            'estimated_execution_time' => $this->estimated_execution_time,
            'has_supporting_documents' => $this->has_supporting_documents,

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
