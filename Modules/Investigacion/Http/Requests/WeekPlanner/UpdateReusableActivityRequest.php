<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;

class UpdateReusableActivityRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a hacer esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'activity_id' => ['sometimes', 'required', Rule::exists(Activity::class, 'id')],

            'activity_type' => ['sometimes', 'required', 'string', 'max:255'],

            'description' => ['sometimes', 'required', 'string'],
            'work_location' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],

            'materials' => ['nullable', 'array'],
            'indicators' => ['nullable', 'array'],
            'logisticSupports' => ['nullable', 'array'],
        ];
    }

    /**
     * Mensajes personalizados (Opcional, si manejas i18n o quieres mensajes específicos)
     */
    public function messages(): array
    {
        return [
            'activity_id.exists' => 'La actividad seleccionada no es válida o no existe en el sistema.',
        ];
    }
}
