<?php

namespace Modules\Investigacion\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class SyncMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'members' => ['present', 'array'],
            'members.*' => ['required', Rule::exists(User::class, 'id')],
        ];
    }
}
