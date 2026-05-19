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
     * Procesa la solicitud de movilización (Lógica 1 a 1).
     */
    public function processDispatch(array $data, User $admin): Dispatch
    {
        return DB::transaction(function () use ($data, $admin) {

            $dispatch = Dispatch::firstOrNew([
                'week_activity_id' => $data['week_activity_id']
            ]);

            $dispatch->admin_id = $admin->id;
            $dispatch->status = $data['status'];
            $dispatch->admin_notes = $data['admin_notes'] ?? null;

            $dispatch->save();

            return $dispatch;
        });
    }

    /**
     * Obtiene solicitudes logísticas filtradas por ID de ubicación.
     */
    public function getStationRequests(?int $locationId = null): Collection
    {
        return WeekActivity::query($locationId, function ($query, $locationId) {
            return $query->whereHas('user', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        })
            // Filtro vital: Solo extraer actividades que solicitan vehículo
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

                // Búsqueda y decodificación del JSONB logístico
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
