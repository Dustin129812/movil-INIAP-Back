<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'search'   => ['nullable', 'string', 'max:100'],
            'estado'   => ['nullable', 'string', 'in:Activo,Inactivo'],
            'tipo'     => ['nullable', 'string', 'in:Investigación,Validación,Demostrativa,Difusión,Aprendizaje'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function saveRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'in:Investigación,Validación,Demostrativa,Difusión,Aprendizaje'],
            'nombre_tecnologia' => ['required', 'string', 'max:255'],
            'tipo_tecnologia' => ['required', 'string', 'in:Material genetico,Recomendación,Producto'],

            'tiene_protocolo' => ['boolean'],
            'aprobado_por_comite' => ['boolean'],
            'fecha_aprobacion_protocolo' => ['nullable', 'required_if:tiene_protocolo,true', 'date'],
            'archivo_protocolo' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'archivo_informe' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'producto_id' => ['nullable', 'exists:products,id'],
            'actividad_id' => ['nullable', 'exists:activities,id'],

            'equipo_tecnico_ids' => ['nullable', 'array'],
            'equipo_tecnico_ids.*' => ['exists:users,id'],
        ];
    }
}
