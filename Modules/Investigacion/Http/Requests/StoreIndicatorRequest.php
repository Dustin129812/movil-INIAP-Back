<?php

namespace Modules\Investigacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:performance_indicators,name',
        ];
    }
}
