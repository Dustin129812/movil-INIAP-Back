<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Administracion\Entities\Vehicle;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Vehicle::class, 'plate')
            ],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'plate.unique' => 'Esta placa ya se encuentra registrada en el sistema de otra estación.',
        ];
    }
}
