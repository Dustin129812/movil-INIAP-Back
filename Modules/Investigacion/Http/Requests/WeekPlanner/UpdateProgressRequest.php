<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'progress' => ['required', 'array'],
            'progress.*.week_activity_id' => ['required', 'integer'],
            'progress.*.status' => ['required', 'in:yes,no,partial'],
            'progress.*.observations' => ['nullable', 'string'],

            'progress.*.evidence' => ['nullable', 'array', 'max:5'],

            'progress.*.evidence.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:3072'
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
