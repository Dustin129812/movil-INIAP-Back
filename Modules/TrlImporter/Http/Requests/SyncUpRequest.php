<?php


namespace Modules\TrlImporter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Modules\TrlImporter\Entities\Tecnologia;

class SyncUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'evaluaciones' => 'required|array|min:1',
            'evaluaciones.*.id' => 'required|string',
            'evaluaciones.*.tecnologia_id' => [
                'required',
                Rule::exists(Tecnologia::class, 'id')
            ],
            'evaluaciones.*.fecha' => 'required|date',
            'evaluaciones.*.tecnico' => 'required|string|max:150',
            'evaluaciones.*.trl_alcanzado' => 'required|integer|between:0,9',
            'evaluaciones.*.respuestas' => 'required|array',
            'evaluaciones.*.observaciones' => 'nullable|string'
        ];
    }

    public function authorize(): bool
    {
        return true; // Ajustar según middleware de autenticación del INIAP
    }
}
