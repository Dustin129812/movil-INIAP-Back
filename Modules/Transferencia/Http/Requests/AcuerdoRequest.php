<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Transferencia\Entities\Organizacion;
class AcuerdoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route()->getActionMethod();

        return match ($action) {
            'index', 'show' => true,
            'store', 'update', 'destroy' => true,
            default => false,
        };
    }

    public function rules(): array
    {
        $action = $this->route()->getActionMethod();

        return match ($action) {
            'index'   => $this->indexRules(),
            'store'   => $this->storeRules(),
            'update'  => $this->updateRules(),
            'show', 'destroy' => [],
            default   => [],
        };
    }

    private function indexRules(): array
    {
        return [
            'organizacion_id' => ['nullable', Rule::exists(Organizacion::class, 'id')],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'location_id'    => ['nullable', 'integer'],
            'huerfanos_only' => ['nullable', 'string'],
            'filter_user_id' => ['nullable', 'string'],
        ];
    }

    private function storeRules(): array
    {
        return [
            'organizacion_id' => ['required', Rule::exists(Organizacion::class, 'id')],
            'fecha_firma'     => ['required', 'date'],
            'anios_vigencia'  => ['required', 'integer', 'min:1', 'max:50'],
            'archivo_acuerdo' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    private function updateRules(): array
    {
        return $this->storeRules();
    }
}
