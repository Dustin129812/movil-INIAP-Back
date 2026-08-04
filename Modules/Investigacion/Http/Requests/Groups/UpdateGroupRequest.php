<?php

namespace Modules\Investigacion\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\Location;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups')->ignore($group->id)->where(function ($query) {
                    return $query->where('rubro_id', $this->rubro_id)
                        ->where('location_id', $this->location_id);
                })
            ],
            'rubro_id' => ['required', Rule::exists(Rubro::class, 'id')],
            'location_id' => ['required', Rule::exists(Location::class, 'id')],
            'parent_id' => ['nullable', Rule::exists(Group::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe otro grupo con este nombre para el mismo rubro y ubicación.',
        ];
    }
}
