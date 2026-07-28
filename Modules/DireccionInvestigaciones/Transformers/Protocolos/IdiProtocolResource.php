<?php

namespace Modules\DireccionInvestigaciones\Transformers\Protocolos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdiProtocolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'activity_title'         => $this->activity_title,
            'project_name'           => $this->project_name,

            // TRL
            'trl_current'            => $this->trl_current,
            'trl_target'             => $this->trl_target,

            // Fechas
            'start_date'             => $this->start_date?->format('Y-m-d'),
            'end_date'               => $this->end_date?->format('Y-m-d'),

            // Presupuesto
            'funding_source'         => $this->funding_source,
            'iniap_role'             => $this->iniap_role,
            'donor_name'             => $this->donor_name,
            'budget_total'           => (float) $this->budget_total,
            'external_amount'        => (float) $this->external_amount,

            // Cálculos dinámicos (siempre útiles para el front)
            'iniap_amount'           => (float) ($this->budget_total - $this->external_amount),
            'external_percent'       => $this->budget_total > 0 ? round(($this->external_amount / $this->budget_total) * 100) : 0,
            'iniap_percent'          => $this->budget_total > 0 ? round((($this->budget_total - $this->external_amount) / $this->budget_total) * 100) : 100,

            // Relaciones BelongsTo (Anidadas limpiamente)
            'station'                => $this->whenLoaded('station', fn() => [
                'id'   => $this->station->id,
                'name' => $this->station->name,
            ]),
            'responsible'            => $this->whenLoaded('responsible', fn() => [
                'id'   => $this->responsible->id,
                'name' => $this->responsible->name,
                'dni'  => $this->responsible->dni ?? null,
            ]),
            'research_line'          => $this->whenLoaded('researchLine', fn() => [
                'id'   => $this->researchLine->id,
                'name' => $this->researchLine->name,
                'area' => $this->whenLoaded('area', fn() => $this->researchLine->area->name ?? null),
            ]),
            'crop'                   => $this->whenLoaded('crop', fn() => [
                'id'   => $this->crop->id,
                'name' => $this->crop->name,
            ]),

            // Relaciones Plurales
            'collaborators'          => $this->whenLoaded('collaborators', fn() =>
            $this->collaborators->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ),
            'influence_cantons'      => $this->whenLoaded('influenceCantons', fn() =>
            $this->influenceCantons->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ),
            'external_collaborators' => $this->external_collaborators,

            // Anexos
            'annexes'                => $this->whenLoaded('annexes', fn() =>
            $this->annexes->map(fn($a) => [
                'id'         => $a->id,
                'file_name'  => $a->file_name,
                'file_size'  => $a->file_size,
                'created_at' => $a->created_at?->format('Y-m-d'),
            ])
            ),
        ];
    }
}
