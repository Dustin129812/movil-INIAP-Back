<?php

namespace App\Modules\Planificacion\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $indicatorId = $this->route('indicator')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('performance_indicators')->ignore($indicatorId),
            ],
        ];
    }
}
