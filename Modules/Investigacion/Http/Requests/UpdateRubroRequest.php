<?php

namespace Modules\Investigacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rubroId = $this->route('rubro')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rubros')->ignore($rubroId),
            ],
        ];
    }
}
