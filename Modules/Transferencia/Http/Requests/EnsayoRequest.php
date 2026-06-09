<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Parroquia;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Province;

class EnsayoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'search'       => ['nullable', 'string', 'max:100'],
            'estado'       => ['nullable', 'string', 'in:Activo,Inactivo'],
            'tipo'         => ['nullable', 'string', 'in:Investigación,Validación,Demostrativa,Difusión,Aprendizaje'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'location_id'    => ['nullable', 'integer'],
            'huerfanos_only' => ['nullable', 'string'],
            'filter_user_id' => ['nullable', 'string'],
            'provincia_id' => ['nullable', 'integer', Rule::exists(Province::class, 'id')],
            'canton_id'    => ['nullable', 'integer', Rule::exists(Canton::class, 'id')],
            'parroquia_id' => ['nullable', 'integer', Rule::exists(Parroquia::class, 'id')],
        ];
    }

    private function saveRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'in:Investigación,Validación,Demostrativa,Difusión,Aprendizaje'],
            'nombre_tecnologia' => ['required', 'string', 'max:255'],
            'tipo_tecnologia' => [
                'required',
                'string',
                Rule::in([
                    'Híbridos, clones o Variedades',
                    'Manejo Integrado de Cultivo',
                    'Sistemas Agroforestales',
                    'Bioinsumos'
                ])
            ],
            'tiene_protocolo' => ['boolean'],
            'aprobado_por_comite' => ['boolean'],
            'fecha_aprobacion_protocolo' => ['nullable', 'required_if:tiene_protocolo,true', 'date'],
            'archivo_protocolo' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'archivo_informe' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'producto_id'  => ['nullable', Rule::exists(Product::class, 'id')],
            'actividad_id' => ['nullable', Rule::exists(Activity::class, 'id')],

            'equipo_tecnico_ids'   => ['nullable', 'array'],
            'equipo_tecnico_ids.*' => [Rule::exists(User::class, 'id')],
        ];
    }
}
