<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;

class UpdateWeeklyActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activityId' => [
                'sometimes',
                'required',
                Rule::exists(Activity::class, 'id')
            ],
            'activity_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['tecnica', 'administrativa'])
            ],
            'description' => ['sometimes', 'required', 'string'],
            'work_location' => ['sometimes', 'required', 'string'],
            'day' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sábado', 'domingo'])
            ],
            'observations' => ['nullable', 'string'],
            'indicators' => ['sometimes', 'array'],

            // Materiales
            'materials' => ['sometimes', 'array'],
            'materials.*.name' => ['required_with:materials', 'string'],
            'materials.*.quantity' => ['required_with:materials', 'integer', 'min:1'],
            'materials.*.description' => ['nullable', 'string'],

            // Apoyos Logísticos
            'logisticSupports' => ['sometimes', 'array'],
            'logisticSupports.*' => [
                'nullable',
                Rule::exists(User::class, 'id')
            ],
        ];
    }
}
