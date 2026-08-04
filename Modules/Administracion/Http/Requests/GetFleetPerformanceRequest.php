<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetFleetPerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
