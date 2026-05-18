<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Kopia\Entities\Proyecto;
use Modules\Kopia\Entities\Lote;
use Modules\Kopia\Entities\Variedad;
use App\Models\User;

class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid_movil' => ['required', 'uuid', Rule::unique(Proyecto::class, 'uuid_movil')],
            'lote_id' => ['required', Rule::exists(Lote::class, 'id')],
            'variedad_id' => ['required', Rule::exists(Variedad::class, 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'objetivos' => ['nullable', 'array'],
            'informacion_adicional' => ['nullable', 'array'],
            'colaboradores' => ['nullable', 'array'],
            'colaboradores.*' => [Rule::exists(User::class, 'id')],
        ];
    }
}
