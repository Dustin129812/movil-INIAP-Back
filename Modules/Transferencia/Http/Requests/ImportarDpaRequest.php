<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarDpaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo_dpa' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo_dpa.required' => 'Debe adjuntar un archivo DPA.',
            'archivo_dpa.file' => 'El archivo subido no es válido.',
            'archivo_dpa.mimes' => 'El archivo debe ser estrictamente en formato .csv, .xlsx o .xls.',
        ];
    }
}
