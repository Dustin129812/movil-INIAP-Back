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

        if ($user->can('approve-any-planning')) {
            return true;
        }

        $weekActivity = WeekActivity::with('user')->find($this->route('activityId'));
        if (!$weekActivity) return false;

        if ($user->hasRole('station-director')) {
            return $weekActivity->user && $weekActivity->user->location_id === $user->location_id;
        }

        return Group::where('responsible_id', $user->id)
            ->whereHas('members', function($q) use ($weekActivity) {
                $q->where('users.id', $weekActivity->user_id);
            })
            ->exists();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['approved', 'rejected', 'reassigned'])],
        ];
    }
}
