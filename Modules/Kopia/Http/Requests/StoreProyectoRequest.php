<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Kopia\Entities\Proyecto;
use Modules\Kopia\Entities\Lote;
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

            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],

            'variedad' => ['required', 'string', 'max:255'],
            'fecha_siembra' => ['nullable', 'date'],
            'tipo_acolchado' => [
                'nullable',
                'string',
                Rule::in(['con_acolchado', 'parcialmente_acolchado', 'sin_acolchado'])
            ],
            'diseno_experimental' => [
                'nullable',
                'string',
                Rule::in(['con_diseno', 'sin_diseno', 'multiplicacion'])
            ],
            'tipo_ensayo' => [
                'nullable',
                'string',
                Rule::in(['investigacion', 'validacion', 'produccion_semillas', 'multiplicacion_semillas', 'refrescamiento'])
            ],
            'objetivos' => ['nullable', 'array'],
            'informacion_adicional' => ['nullable', 'array'],
            'colaboradores' => ['nullable', 'array'],
            'colaboradores.*' => [Rule::exists(User::class, 'id')],
        ];
    }
}
