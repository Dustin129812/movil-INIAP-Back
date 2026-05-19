<?php

namespace Modules\Transferencia\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class AcuerdoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fechaVencimiento = $this->fecha_firma ? Carbon::parse($this->fecha_firma)->addYears($this->anios_vigencia) : null;
        $esVigente = $fechaVencimiento ? $fechaVencimiento->isFuture() : false;

        return [
            'id' => $this->id,
            'fecha_firma' => $this->fecha_firma?->format('Y-m-d'),
            'anios_vigencia' => $this->anios_vigencia,
            'estado_vigencia' => [
                'fecha_vencimiento' => $fechaVencimiento?->format('Y-m-d'),
                'es_vigente' => $esVigente,
            ],
            'archivo_url' => $this->archivo_acuerdo_path
                ? URL::temporarySignedRoute(
                    'api.transferencia.acuerdos.download',
                    now()->addMinutes(30),
                    ['acuerdo' => $this->id]
                )
                : null,
            'organizacion' => new OrganizacionResource($this->whenLoaded('organizacion')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
