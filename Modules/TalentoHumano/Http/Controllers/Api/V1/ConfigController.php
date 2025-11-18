<?php

namespace Modules\TalentoHumano\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TalentoHumano\Entities\ThActivityType;
use Modules\TalentoHumano\Entities\ThEmployeeConfig;
use Modules\TalentoHumano\Entities\ThHoliday;
use Modules\TalentoHumano\Entities\ThSetting;
use Modules\TalentoHumano\Entities\ThVehicle;
use Illuminate\Http\JsonResponse;
use App\Models\User;

/**
 * NOTA: Este controlador gestiona múltiples recursos.
 * Las rutas apiResource() apuntarán a los métodos apropiados.
 * Ej: GET /config/vehicles -> vehicles_index()
 * POST /config/vehicles -> vehicles_store()
 * * Esto requiere personalizar las rutas (lo haremos luego).
 * Por ahora, definimos los métodos.
 */
class ConfigController extends Controller
{
    // --- Gestión de Vehículos ---

    public function vehiclesIndex(): JsonResponse
    {
        return response()->json(ThVehicle::all());
    }

    public function vehiclesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'placa' => 'required|string|unique:th_vehicles,placa',
            'model' => 'required|string',
            'is_active' => 'boolean',
        ]);
        $vehicle = ThVehicle::create($data);
        return response()->json($vehicle, 201);
    }

    public function vehiclesUpdate(Request $request, ThVehicle $vehicle): JsonResponse
    {
        $data = $request->validate([
            'model' => 'sometimes|required|string',
            'is_active' => 'sometimes|boolean',
        ]);
        $vehicle->update($data);
        return response()->json($vehicle);
    }

    // --- Gestión de Tipos de Actividad ---

    public function activitiesIndex(): JsonResponse
    {
        return response()->json(ThActivityType::all());
    }

    public function activitiesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|unique:th_activity_types,name',
            'is_active' => 'boolean',
        ]);
        $activity = ThActivityType::create($data);
        return response()->json($activity, 201);
    }

    public function activitiesUpdate(Request $request, ThActivityType $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:th_activity_types,name,' . $activity->id,
            'is_active' => 'sometimes|boolean',
        ]);
        $activity->update($data);
        return response()->json($activity);
    }

    // --- Gestión de Feriados ---

    public function holidaysIndex(): JsonResponse
    {
        return response()->json(ThHoliday::all());
    }

    public function holidaysStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date|unique:th_holidays,date',
            'name' => 'required|string',
        ]);
        $holiday = ThHoliday::create($data);
        return response()->json($holiday, 201);
    }

    public function holidaysDestroy(ThHoliday $holiday): JsonResponse
    {
        $holiday->delete();
        return response()->json(null, 204);
    }

    // --- Gestión de Configuración de Empleados (RMU) ---

    public function employeeConfigsIndex(): JsonResponse
    {
        return response()->json(ThEmployeeConfig::with('user:id,name')->get());
    }

    public function employeeConfigsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id|unique:th_employee_configs,user_id',
            'rmu' => 'required|numeric|min:0',
        ]);
        $config = ThEmployeeConfig::create($data);
        return response()->json($config, 201);
    }

    public function employeeConfigsUpdate(Request $request, ThEmployeeConfig $config): JsonResponse
    {
        $data = $request->validate([
            'rmu' => 'required|numeric|min:0',
        ]);
        $config->update($data);
        return response()->json($config);
    }

    /**
     * Devuelve una lista simple de todos los usuarios para los desplegables.
     * Acepta un ?search= para filtrar.
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $query = User::orderBy('name', 'asc');

        if ($search) {
            // Busca por nombre o DNI (cédula)
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('dni', 'ilike', '%' . $search . '%');
            });
        }

        // Limita los resultados a 20 para que la búsqueda sea rápida
        $users = $query->limit(20)->get(['id', 'name']);

        return response()->json($users);
    }

    // --- Gestión de Autoridades ---

    public function getAuthorities()
    {
        $settings = ThSetting::whereIn('key', ['daf_authority_id', 'mobility_authority_id'])->get();

        // Formateamos para devolver también el nombre del usuario actual
        $data = $settings->mapWithKeys(function ($item) {
            $user = $item->value ? User::find($item->value) : null;
            return [$item->key => [
                'user_id' => $item->value,
                'user_name' => $user ? $user->name : 'No asignado'
            ]];
        });

        return response()->json($data);
    }

    public function updateAuthorities(Request $request)
    {
        $data = $request->validate([
            'daf_authority_id' => 'nullable|exists:users,id',
            'mobility_authority_id' => 'nullable|exists:users,id',
        ]);

        foreach ($data as $key => $value) {
            ThSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Autoridades actualizadas correctamente.']);
    }
}
