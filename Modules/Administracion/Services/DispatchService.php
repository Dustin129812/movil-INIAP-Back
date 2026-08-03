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

            if ($dispatch->vehicle_id) {
                if ($data['status'] === 'processing') {
                    Vehicle::where('id', $dispatch->vehicle_id)
                        ->update(['is_available' => false]);
                } elseif (in_array($data['status'], ['dispatched', 'rejected'])) {
                    $hasOtherActiveDispatches = Dispatch::where('vehicle_id', $dispatch->vehicle_id)
                        ->where('status', 'processing')
                        ->where('id', '!=', $dispatch->id)
                        ->exists();

                    if (!$hasOtherActiveDispatches) {
                        Vehicle::where('id', $dispatch->vehicle_id)
                            ->update(['is_available' => true]);
                    }
                }
            }

            return $dispatch;
        });
    }

    /**
     * Obtiene las solicitudes de la estación con filtro opcional de fechas.
     */
    public function getStationRequests(?int $locationId = null, ?string $startDate = null, ?string $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        return WeekActivity::query()
            ->when($locationId, function ($query, $locationId) {
                $query->whereHas('user', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            })
            // Añadido: Filtro de ventana operativa
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
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
            ->get();
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
