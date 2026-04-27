<?php

namespace Modules\Administracion\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\Administracion\Entities\Dispatch;
use Modules\Investigacion\Entities\WeekActivity;

class DispatchService
{
    /**
     * Procesa la solicitud de despacho, confirmando cantidades y actualizando el estado.
     * * @param array $data Datos validados desde el FormRequest
     * @param User $admin El usuario administrador en sesión
     * @return Dispatch
     */
    public function processDispatch(array $data, User $admin): Dispatch
    {
        return DB::transaction(function () use ($data, $admin) {

            $dispatch = Dispatch::firstOrNew([
                'week_activity_id' => $data['week_activity_id']
            ]);

            if (!$dispatch->exists || empty($dispatch->requested_items)) {
                $weekActivity = WeekActivity::with('materials')->findOrFail($data['week_activity_id']);
                $dispatch->requested_items = $this->buildRequestedItemsSnapshot($weekActivity);
            }

            $dispatch->admin_id = $admin->id;
            $dispatch->status = $data['status'];
            $dispatch->admin_notes = $data['admin_notes'] ?? null;

            if ($data['status'] === 'dispatched' && isset($data['dispatched_items'])) {
                $dispatch->dispatched_items = $data['dispatched_items'];
            }

            $dispatch->save();

            return $dispatch;
        });
    }

    /**
     * Obtiene solicitudes filtradas por ID de ubicación.
     * @param int|null $locationId
     * @return Collection
     */
    public function getStationRequests(?int $locationId = null): Collection
    {
        return WeekActivity::has('materials')
            // Corregimos el filtro: Buscamos actividades cuyos usuarios pertenezcan a la locación
            ->when($locationId, function ($query, $locationId) {
                return $query->whereHas('user', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
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

                return (object) [
                    'id' => $weekActivity->id,
                    'date' => $weekActivity->date,
                    'technician_name' => $weekActivity->user->name,
                    'product_name' => $weekActivity->activity->product->name ?? 'Sin producto',
                    'activity_description' => $weekActivity->description,
                    'work_location' => $weekActivity->work_location,
                    'status' => $status,
                    'requested_items' => $dispatch && $dispatch->requested_items
                        ? $dispatch->requested_items
                        : $this->buildRequestedItemsSnapshot($weekActivity),
                    'dispatched_items' => $dispatch ? $dispatch->dispatched_items : null,
                    'admin_notes' => $dispatch ? $dispatch->admin_notes : null,
                ];
            });
    }

    /**
     * Construye un array estructurado (snapshot) leyendo la tabla pivote de materiales.
     * * @param WeekActivity $weekActivity
     * @return array
     */
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
