<?php

namespace Modules\TrlImporter\Services;

use Illuminate\Support\Facades\DB;
use Modules\TrlImporter\Entities\Evaluacion;
use Modules\TrlImporter\Entities\Respuesta;

class SyncUpService
{
    public function receiveEvaluations(array $evaluaciones): array
    {
        return DB::transaction(function () use ($evaluaciones) {
            foreach ($evaluaciones as $data) {
                \Log::info("Guardando evaluación ID: " . $data['id']);

                $eval = Evaluacion::updateOrCreate(
                    ['id' => $data['id']],
                    [
                        'tecnologia_id' => $data['tecnologia_id'],
                        'fecha'         => $data['fecha'],
                        'tecnico'       => $data['tecnico'],
                        'observaciones' => $data['observaciones'] ?? null,
                    ]
                );

                foreach ($data['respuestas'] as $matrizId => $cumple) {
                    Respuesta::updateOrCreate(
                        ['id' => "RESP-{$eval->id}-{$matrizId}"],
                        [
                            'evaluacion_id' => $eval->id,
                            'matriz_trl_id' => $matrizId,
                            'cumple'        => (bool)$cumple
                        ]
                    );
                }

                DB::table('trl.tecnologias')
                    ->where('id', $data['tecnologia_id'])
                    ->update(['trl_base' => $data['trl_alcanzado']]);
            }
            return ['success' => true];
        });
    }
}
