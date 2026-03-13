<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Province;

class OrganizacionRequest extends FormRequest
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
            'store', 'update' => $this->saveRules(),
            'show', 'destroy' => [],
            default   => [],
        };
    }

    private function indexRules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'tipo'     => ['nullable', 'string', 'in:Legalizada,Grupo de productores,Comuna o Recinto'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function saveRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_organizacion' => ['required', 'string', 'in:Legalizada,Grupo de productores,Comuna o Recinto'],
            'participantes_hombres' => ['required', 'integer', 'min:0'],
            'participantes_mujeres' => ['required', 'integer', 'min:0'],

            'provincia_id' => ['required', Rule::exists(Province::class, 'id')],
            'canton_id'    => ['required', Rule::exists(Canton::class, 'id')],
            'parroquia_id' => ['required', Rule::exists(Parroquia::class, 'id')],
        ];
    }
}
