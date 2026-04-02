<?php

namespace Modules\TrlImporter\Services;

use Illuminate\Support\Facades\DB;
use Modules\TrlImporter\Entities\Evaluacion;
use Modules\TrlImporter\Entities\Respuesta;

class SyncUpService
{
    public function receiveEvaluations(array $evaluaciones): array
    {
        \Log::info("Procesando lote de evaluaciones", ['cantidad' => count($evaluaciones)]);

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

                // Guardamos el detalle de las respuestas [cite: 19, 30, 41, 52]
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

                // Actualizamos el TRL maestro de la tecnología [cite: 13]
                DB::table('trl.tecnologias')
                    ->where('id', $data['tecnologia_id'])
                    ->update(['trl_base' => $data['trl_alcanzado']]);
            }
            return ['success' => true];
        });
    }
}
