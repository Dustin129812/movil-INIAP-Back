<?php

namespace Modules\TrlImporter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo_excel' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ];
    }
}
