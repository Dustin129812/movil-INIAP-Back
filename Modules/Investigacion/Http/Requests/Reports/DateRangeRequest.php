<?php

namespace Modules\Investigacion\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class DateRangeRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
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
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }

    /**
     * (Opcional) Mensajes personalizados en español para el frontend.
     * Si ya manejas la traducción a nivel de framework, puedes omitir este método.
     */
    public function messages(): array
    {
        return [
            'start_date.required'     => 'La fecha de inicio es obligatoria.',
            'start_date.date_format'  => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'end_date.required'       => 'La fecha de fin es obligatoria.',
            'end_date.date_format'    => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
