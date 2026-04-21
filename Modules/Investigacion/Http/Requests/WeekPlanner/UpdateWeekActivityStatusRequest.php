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

        if ($user->hasRole('station-director')) {
            return true;
        }

        $weekActivityId = $this->route('activity');

        $weekActivity = WeekActivity::with('activity.product')->find($weekActivityId);

        if (!$weekActivity || !$weekActivity->activity || !$weekActivity->activity->product) {
            return false;
        }

        $product = $weekActivity->activity->product;

        return Group::where('location_id', $product->location_id)
            ->where('rubro_id', $product->rubro_id)
            ->where('responsible_id', $user->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['approved', 'rejected', 'reassigned'])],
        ];
    }
}
