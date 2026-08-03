<?php

namespace Modules\Investigacion\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class ChangeResponsibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'responsible_id' => [
                'required',
                Rule::exists(User::class, 'id'),
                Rule::exists('group_user', 'user_id')->where('group_id', $group->id),
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'responsible_id.exists' => 'El usuario seleccionado no es un miembro válido de este grupo o no existe.',
        ];
    }
}
