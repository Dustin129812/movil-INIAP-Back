<?php

namespace Modules\Administracion\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\Administracion\Entities\Dispatch;
use Modules\Administracion\Entities\Vehicle;
use Modules\Investigacion\Entities\WeekActivity;

class DispatchService
{
    public function processDispatch(array $data, User $admin): Dispatch
    {
        return DB::transaction(function () use ($data, $admin) {
            $weekActivity = WeekActivity::with('materials')->findOrFail($data['week_activity_id']);

            $dispatch = Dispatch::firstOrNew(['week_activity_id' => $data['week_activity_id']]);

            $previousVehicleId = $dispatch->vehicle_id;

            $dispatch->admin_id = $admin->id;
            $dispatch->status = $data['status'];
            $dispatch->admin_notes = $data['admin_notes'] ?? null;
            $dispatch->vehicle_id = $data['vehicle_id'] ?? $dispatch->vehicle_id;
            $dispatch->driver_id = $data['driver_id'] ?? $dispatch->driver_id;

            if (!$dispatch->exists || empty($dispatch->requested_items)) {
                $dispatch->requested_items = $this->buildRequestedItemsSnapshot($weekActivity);
            }

            if ($data['status'] === 'dispatched' && isset($data['dispatched_items'])) {
                $dispatch->dispatched_items = $data['dispatched_items'];
            }

            $dispatch->save();

            if ($data['status'] === 'processing' && $dispatch->vehicle_id) {
                Vehicle::where('id', $dispatch->vehicle_id)
                    ->update(['is_available' => false]);
            }

            // Si el estado pasa a finalizado o rechazado, liberamos el vehículo
            if (in_array($data['status'], ['dispatched', 'rejected']) && $dispatch->vehicle_id) {
                Vehicle::where('id', $dispatch->vehicle_id)
                    ->update(['is_available' => true]);
            }

            return $dispatch;
        });
    }

    public function getStationRequests(?int $locationId = null): Collection
    {
        return WeekActivity::query()
            ->when($locationId, function ($query, $locationId) {
                $query->whereHas('user', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            })
            ->whereHas('materials', function ($query) {
                $query->where('material_week_activity.request_type', 'logistics');
            })
            ->with([
                'user:id,name,email,location_id',
                'activity.product',
                'materials',
                'dispatch'
            ])
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($weekActivity) {
                $dispatch = $weekActivity->dispatch;
                $status = $dispatch ? $dispatch->status : 'pending';

                $logisticItem = $weekActivity->materials->firstWhere('pivot.request_type', 'logistics');

                $mobilizationData = [];
                if ($logisticItem) {
                    $metadata = $logisticItem->pivot->metadata;
                    $decodedMeta = is_string($metadata) ? json_decode($metadata, true) : ($metadata ?? []);

                    $mobilizationData = [
                        'type'           => $decodedMeta['tipo'] ?? 'interna',
                        'destination'    => $decodedMeta['lugar'] ?? $weekActivity->work_location,
                        'departure_time' => $decodedMeta['fecha_desde'] ?? 'Por definir',
                        'return_time'    => $decodedMeta['fecha_hasta'] ?? 'Por definir',
                        'justification'  => $logisticItem->pivot->description ?? $weekActivity->description,
                        'passengers'     => $logisticItem->pivot->quantity ?? 1,
                    ];
                }

                return (object) [
                    'id' => $weekActivity->id,
                    'date' => $weekActivity->date,
                    'technician_name' => $weekActivity->user->name,
                    'activity_description' => $weekActivity->description,
                    'status' => $status,
                    'mobilization' => $mobilizationData,
                    'requested_items' => $this->buildRequestedItemsSnapshot($weekActivity),
                    'admin_notes' => $dispatch ? $dispatch->admin_notes : null,
                ];
            });
    }

    private function buildRequestedItemsSnapshot(WeekActivity $weekActivity): array
    {
        return $weekActivity->materials->map(function ($material) {
            return [
                'material_id' => $material->id,
                'name' => $material->name,
                'requested_qty' => $material->pivot->quantity ?? 0,
                'description' => $material->pivot->description ?? '',
            ];
        })->toArray();
    }
}
