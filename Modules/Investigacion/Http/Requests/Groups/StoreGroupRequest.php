<?php

namespace Modules\Investigacion\Http\Requests\Groups;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Rubro;

class StoreGroupRequest extends FormRequest
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
                'max:255',
                Rule::unique('groups')->where(function ($query) {
                    return $query->where('rubro_id', $this->rubro_id)
                        ->where('location_id', $this->location_id);
                })
            ],
            'rubro_id' => ['required', Rule::exists(Rubro::class, 'id')],
            'location_id' => ['required', Rule::exists(Location::class, 'id')],
            'members' => ['present', 'array'],
            'members.*' => ['required', Rule::exists(User::class, 'id')],
            'responsible_id' => ['required', Rule::exists(User::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un grupo con este nombre para el mismo rubro y ubicación.',
            'responsible_id.required' => 'Debes seleccionar un responsable para el grupo.',
        ];
    }
}
