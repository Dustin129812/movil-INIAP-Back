<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\Group;

class UpdateWeekActivityStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $weekActivity = WeekActivity::with('user')->find($this->route('activityId'));

        if (!$weekActivity || !$weekActivity->user) return false;

        if ($user->can('approve-any-planning')) return true;

        if ($user->hasRole('station-director')) {
            $isTargetResponsible = Group::where('location_id', $user->location_id)
                ->where('responsible_id', $weekActivity->user_id)
                ->exists();

            return $isTargetResponsible && $weekActivity->user->location_id === $user->location_id;
        }

        return Group::where('responsible_id', $user->id)
            ->whereHas('members', function($q) use ($weekActivity) {
                $q->where('users.id', $weekActivity->user_id);
            })
            ->where('responsible_id', '!=', $weekActivity->user_id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['approved', 'rejected', 'reassigned'])],
        ];
    }
}
