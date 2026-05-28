<?php

namespace Modules\Administracion\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Administracion\Entities\Vehicle;

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
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists(Vehicle::class, 'id')
            ],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')
            ],
            'dispatched_items' => ['nullable', 'array'],
            'dispatched_items.*.material_id' => ['required_with:dispatched_items', 'integer'],
            'dispatched_items.*.name' => ['required_with:dispatched_items', 'string'],
            'dispatched_items.*.requested_qty' => ['required_with:dispatched_items', 'numeric'],
            'dispatched_items.*.dispatched_qty' => ['required_with:dispatched_items', 'numeric', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
