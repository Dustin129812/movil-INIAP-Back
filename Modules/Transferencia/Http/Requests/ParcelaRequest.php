<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Entities\Acuerdo;

class ParcelaRequest extends FormRequest
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
            default   => [],
        };
    }

    private function indexRules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:100'],
            'estado'    => ['nullable', 'string', 'in:Planificada,Implementado,Perdido,Dado de baja,Finalizado'],
            'ensayo_id' => ['nullable', Rule::exists(Ensayo::class, 'id')],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function saveRules(): array
    {
        return [
            // Llaves maestras
            'ensayo_id'       => ['required', Rule::exists(Ensayo::class, 'id')],
            'organizacion_id' => ['required', Rule::exists(Organizacion::class, 'id')],
            'acuerdo_id'      => ['nullable', Rule::exists(Acuerdo::class, 'id')],
            'libro_campo_id'  => ['nullable', 'exists:produccion.libros_campo,id'],

            // Datos de ubicación
            'nombre'       => ['required', 'string', 'max:255'],
            'provincia_id' => ['required', 'exists:provinces,id'],
            'canton_id'    => ['required', 'exists:cantons,id'],
            'parroquia_id' => ['required', 'exists:parroquias,id'],
            'localidad'    => ['nullable', 'string', 'max:255'],

            // Coordenadas (Se reciben como string/decimal, PostGIS hará la magia interna después)
            'coordenada_x' => ['nullable', 'string'],
            'coordenada_y' => ['nullable', 'string'],

            // Fechas y Estado
            'fecha_implementacion' => ['nullable', 'date'],
            'fecha_finalizacion'   => ['nullable', 'date', 'after_or_equal:fecha_implementacion'],
            'estado'               => ['required', 'string', 'in:Planificada,Implementado,Perdido,Dado de baja,Finalizado'],
        ];
    }
}
