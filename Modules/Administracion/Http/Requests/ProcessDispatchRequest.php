<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\WeekActivity;

class ProcessDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_activity_id' => [
                'required',
                Rule::exists(WeekActivity::class, 'id')
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['processing', 'dispatched', 'rejected'])
            ],
            'dispatched_items' => ['required_if:status,dispatched', 'array'],
            'dispatched_items.*.material_id' => ['required_with:dispatched_items', 'integer'],
            'dispatched_items.*.name' => ['required_with:dispatched_items', 'string'],
            'dispatched_items.*.requested_qty' => ['required_with:dispatched_items', 'numeric'],
            'dispatched_items.*.dispatched_qty' => ['required_with:dispatched_items', 'numeric', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
