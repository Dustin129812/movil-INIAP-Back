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

    /**
     * Estandariza las llaves de camelCase a snake_case antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'activity_id' => $this->input('activity_id') ?? $this->input('activityId'),
            'activity_type' => $this->input('activity_type') ?? $this->input('activityType'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'activity_id' => ['required', Rule::exists(Activity::class, 'id')],
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
