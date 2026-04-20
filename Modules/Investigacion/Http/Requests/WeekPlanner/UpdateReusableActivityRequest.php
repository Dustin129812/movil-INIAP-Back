<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;

class UpdateReusableActivityRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'activity_id' => ['sometimes', 'required', Rule::exists(Activity::class, 'id')],
            'activity_type' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'work_location' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'materials' => ['nullable', 'array'],
            'indicators' => ['nullable', 'array'],
            'logisticSupports' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_id.exists' => 'La actividad seleccionada no es válida o no existe en el sistema.',
        ];
    }
}
