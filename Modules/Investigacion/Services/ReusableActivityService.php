<?php

namespace Modules\Investigacion\Services;

use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\ReusableActivity;

class ReusableActivityService
{
    public function store(array $data, int $userId): ReusableActivity
    {
        return DB::transaction(function () use ($data, $userId) {
            $reusable = ReusableActivity::create([
                'user_id' => $userId,
                'activity_id' => $data['activity_id'],
                'activity_type' => $data['activity_type'],
                'name' => $data['name'],
                'description' => $data['description'],
                'work_location' => $data['work_location'] ?? null,
                'observations' => $data['observations'] ?? null,
            ]);

            $this->syncRelations($reusable, $data);

            return $reusable->load(['activity.product', 'materials', 'performanceIndicators', 'logisticSupportUsers']);        });
    }

    public function update(ReusableActivity $reusable, array $data): ReusableActivity
    {
        return DB::transaction(function () use ($reusable, $data) {
            $reusable->update([
                'activity_id' => $data['activity_id'] ?? $reusable->activity_id,
                'activity_type' => $data['activity_type'] ?? $reusable->activity_type,
                'name' => $data['name'] ?? $reusable->name,
                'description' => $data['description'] ?? $reusable->description,
                'work_location' => $data['work_location'] ?? $reusable->work_location,
                'observations' => $data['observations'] ?? $reusable->observations,
            ]);

            $this->syncRelations($reusable, $data);

            return $reusable->load(['activity.product', 'materials', 'performanceIndicators', 'logisticSupportUsers']);        });
    }

    public function destroy(ReusableActivity $reusableActivity): void
    {
        $reusableActivity->delete();
    }

    /**
     * DRY: Centraliza la lógica de sincronización de relaciones para store y update.
     */
    private function syncRelations(ReusableActivity $reusable, array $data): void
    {
        if (isset($data['materials'])) {
            $materialSyncData = [];
            foreach ($data['materials'] as $material) {
                $materialSyncData[$material['id']] = [
                    'quantity' => $material['pivot']['quantity'] ?? null,
                    'description' => $material['pivot']['description'] ?? null
                ];
            }
            $reusable->materials()->sync($materialSyncData);
        }

        if (isset($data['indicators'])) {
            $reusable->performanceIndicators()->sync($data['indicators']);
        }

        if (isset($data['logisticSupports'])) {
            $reusable->logisticSupportUsers()->sync($data['logisticSupports']);
        }
    }
}
