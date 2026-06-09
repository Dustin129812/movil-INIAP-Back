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
        return true;
    }

    public function rules(): array
    {
        return match (true) {
            $this->isMethod('GET') && $this->route()->getName() === 'organizaciones.index' => $this->indexRules(),
            $this->isMethod('POST'), $this->isMethod('PUT'), $this->isMethod('PATCH')      => $this->saveRules(),
            default                                                                         => [],
        };
    }

    private function indexRules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'tipo'     => ['nullable', 'string', 'in:Legalizada,Grupo de productores,Comuna o Recinto,Gad,Instituto de Educacion'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'location_id'    => ['nullable', 'integer'],
            'huerfanos_only' => ['nullable', 'string'],
            'filter_user_id' => ['nullable', 'string'],
        ];
    }

    private function saveRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_organizacion' => ['required', 'string', 'in:Legalizada,Grupo de productores,Comuna o Recinto,Gad,Instituto de Educacion'],
            'participantes_hombres' => ['required', 'integer', 'min:0'],
            'participantes_mujeres' => ['required', 'integer', 'min:0'],
            'provincia_id' => ['required', Rule::exists(Province::class, 'id')],
            'canton_id'    => ['required', Rule::exists(Canton::class, 'id')],
            'parroquia_id' => ['required', Rule::exists(Parroquia::class, 'id')],
        ];
    }
}
