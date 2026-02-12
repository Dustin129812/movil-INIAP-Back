<?php

namespace Modules\Investigacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdiProtocolRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 1. Identificación
            'project_name'      => 'nullable|string|max:255',
            'activity_title'    => 'required|string|max:255',

            // 2. Ubicación
            'station_id'        => 'required|exists:locations,id',
            'canton_ids'        => 'required|array', // Para la tabla pivote
            'canton_ids.*'      => 'exists:cantons,id',

            // 3. Técnica
            'research_line_id'  => 'required|exists:research_lines,id',
            'crop_id'           => 'required|exists:crops,id',
            'trl_current'       => 'required|integer|min:1|max:9',
            'trl_target'        => 'required|integer|min:1|max:9|gte:trl_current', // Target >= Current

            // 4. Fechas y Responsables
            'responsible_id'    => 'required|exists:users,id',
            'collaborator_ids'  => 'nullable|array', // Pivote internos
            'collaborator_ids.*'=> 'exists:users,id',
            'external_collaborators' => 'nullable|string', // Texto simple
            'iniap_role' => 'required|string|max:255',

            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',

            // 5. Presupuesto
            'funding_source'    => 'required|string',
            'budget_total'      => 'required|numeric|min:0',
            'external_amount'   => 'nullable|numeric|min:0',

            // 6. Anexo
            'annexes' => 'nullable|array',
            'annexes.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ];
    }

    public function attributes()
    {
        return [
            'station_id' => 'Estación Experimental',
            'crop_id' => 'Rubro/Cultivo',
            'canton_ids' => 'Cantones de influencia',
        ];
    }
}
