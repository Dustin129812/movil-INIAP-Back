<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use Modules\Investigacion\Entities\Location;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('administracion.warehouses', 'name')->where(function ($query) {
                    return $query->where('location_id', $this->location_id);
                })
            ],

            'location_id' => [
                'required',
                'integer',
                Rule::exists(Location::class, 'id')
            ],

            'responsible_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')
            ],

            'is_active' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una bodega con este nombre en la ubicación seleccionada.',
            'responsible_id.exists' => 'El usuario asignado como responsable no existe en el sistema.',
        ];
    }
}
