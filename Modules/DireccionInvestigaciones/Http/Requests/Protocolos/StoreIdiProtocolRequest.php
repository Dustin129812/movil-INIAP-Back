<?php


namespace Modules\DireccionInvestigaciones\Http\Requests\Protocolos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Crops;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\ResearchLine;
use Modules\Investigacion\Entities\Canton;


class StoreIdiProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identificación
            'activity_title' => ['required', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'station_id' => ['required', 'integer', Rule::exists(Location::class, 'id')],
            'canton_ids' => ['nullable', 'array'],
            'canton_ids.*' => ['integer', Rule::exists(Canton::class, 'id')],

            // Técnico
            'research_line_id' => ['required', 'integer', Rule::exists(ResearchLine::class, 'id')],
            'crop_id' => ['required', 'integer', Rule::exists(Crops::class, 'id')],
            'trl_current' => ['required', 'integer', 'min:1', 'max:9'],
            'trl_target' => ['required', 'integer', 'min:1', 'max:9', 'gte:trl_current'],

            // Cronograma y Equipo
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'responsible_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'collaborator_ids' => ['nullable', 'array'],
            'collaborator_ids.*' => ['integer', Rule::exists(User::class, 'id')],
            'external_collaborators' => ['nullable', 'string'],

            // Presupuesto
            'funding_source' => ['required', 'string', 'max:100'],
            'iniap_role' => ['required', 'string', 'max:100'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'budget_total' => ['required', 'numeric', 'min:0'],
            'external_amount' => ['required', 'numeric', 'min:0', 'lte:budget_total'],

            // Anexos
            'annexes' => ['nullable', 'array'],
            'annexes.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
