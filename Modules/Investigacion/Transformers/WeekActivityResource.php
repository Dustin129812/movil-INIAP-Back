<?php

namespace Modules\Investigacion\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class WeekActivityResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

        $isReviewDashboard = isset($this->display_user_id);

        return [
            'id' => $this->id,
            'activity_id' => $this->activity->id ?? null,
            'product_id' => $this->activity->product_id ?? ($this->activity->product->id ?? null),
            'description' => $this->description,
            'date' => Carbon::parse($this->date)->format('Y-m-d'),
            'product_name' => $this->activity->product->name ?? null,
            'activity_name' => $this->activity->description ?? null,
            'status' => $this->status,
            'percentage' => $this->percentage,
            'observations' => $this->observations,

            'user_id' => $this->display_user_id ?? $this->user_id,
            'userName' => $this->display_user_name ?? ($this->user->name ?? 'Usuario'),

            'is_owner' => $isReviewDashboard ? $this->is_owner_flag : (bool) ($this->user_id === $user->id),

            'owner_name' => $this->supported_owner_name ?? ($this->user->name ?? 'Compañero'),

            'my_support_status' => $this->user_id !== $user->id
                ? $this->logisticSupportUsers->where('id', $user->id)->first()->pivot->status ?? 'pending'
                : null,

            'logistic_supports' => $this->logisticSupportUsers->map(function ($supportUser) {
                return ['id' => $supportUser->id, 'name' => $supportUser->name];
            }),

            'monthly_plannig' => $this->activity->monthlyProgress->map(function ($progress) {
                return [
                    'month' => Carbon::parse($progress->month)->format('Y-m-d'),
                    'percentage' => $progress->percentage,
                ];
            }),

            'execution_progress' => $this->activity->weeklyActivities->map(function ($exec) {
                return [
                    'week_id' => $exec->id,
                    'date' => Carbon::parse($exec->date)->format('Y-m-d'),
                    'reported_percentage' => $exec->percentage,
                ];
            }),
        ];
    }
}
