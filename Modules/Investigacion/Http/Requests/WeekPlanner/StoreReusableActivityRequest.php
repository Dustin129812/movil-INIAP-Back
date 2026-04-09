<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;

class StoreReusableActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'activityId' => ['required', Rule::exists(Activity::class, 'id')],
            'activity_type' => ['required', 'string'],
            'description' => ['required', 'string'],
            'work_location' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'materials' => ['nullable', 'array'],
            'indicators' => ['nullable', 'array'],
            'logisticSupports' => ['nullable', 'array'],
        ];
    }
}
