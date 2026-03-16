<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\WeekActivity;

class UpdateProgressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'progress' => ['required', 'array'],
            'progress.*.week_activity_id' => [
                'required',
                Rule::exists(WeekActivity::class, 'id') // Cero strings mágicos
            ],
            'progress.*.status' => [
                'required',
                'string',
                Rule::in(['yes', 'no', 'partial'])
            ],
            'progress.*.observations' => ['nullable', 'string'],
        ];
    }
}
