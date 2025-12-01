<?php

namespace Modules\TalentoHumano\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\TalentoHumano\Entities\ThActivityType;
use Modules\TalentoHumano\Entities\ThEmployeeConfig;
use Modules\TalentoHumano\Entities\ThOvertimeEntry;
use Modules\TalentoHumano\Entities\ThOvertimeReport;
use Modules\TalentoHumano\Entities\ThVehicle;
use Modules\TalentoHumano\Http\Requests\StoreEntryRequest;
use Modules\TalentoHumano\Http\Requests\UpdateEntryRequest;
use Illuminate\Http\JsonResponse;
use Modules\TalentoHumano\Services\OvertimeCalculationService;

class DriverReportController extends Controller
{

    private const RMU_HOURS_DIVISOR = 240;

    protected $calculationService;
    public function __construct(OvertimeCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Devuelve los datos para los desplegables del formulario
     * GET /api/v1/talento-humano/form-data
     */
    public function getFormData(): JsonResponse
    {
        $vehicles = ThVehicle::where('is_active', true)->get(['placa', 'model']);
        $activities = ThActivityType::where('is_active', true)->get(['id', 'name']);

        return response()->json([
            'vehicles' => $vehicles,
            'activity_types' => $activities,
        ]);
    }

    /**
     * Obtiene el reporte del mes actual, o lo crea si no existe.
     * GET /api/v1/talento-humano/reports/current
     */
    public function getCurrentReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 1. Verificar configuración (igual que antes)
        $config = ThEmployeeConfig::where('user_id', $user->id)->first();
        if (!$config) {
            return response()->json([
                'message' => 'Usuario no configurado para registrar horas extras.'
            ], 403);
        }

        // 2. Determinar fecha solicitada (Por defecto HOY)
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // 3. Buscar o crear el reporte para ESA fecha específica
        $report = ThOvertimeReport::firstOrCreate(
            [
                'driver_id' => $user->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'status' => 'borrador',
                'rmu_at_submission' => $config->rmu,
                'hour_value' => $config->rmu / 240
            ]
        );

        // Recalculamos para asegurar que la UI vea los datos frescos
        if($report->status === 'borrador') {
            $this->calculationService->calculateRawEntries($report);
        }

        $report->load('entries.activityType', 'entries.vehicle');

        return response()->json($report);
    }

    /**
     * Almacena una nueva entrada (viaje)
     * POST /api/v1/talento-humano/entries
     */
    public function storeEntry(StoreEntryRequest $request): JsonResponse
    {
        // 1. Obtenemos los datos validados
        $data = $request->validated();

        // 2. Analizamos la fecha de la actividad
        $entryDate = Carbon::parse($data['date']);
        $targetMonth = $entryDate->month;
        $targetYear = $entryDate->year;
        $userId = Auth::id();

        // 3. ENRUTAMIENTO DINÁMICO:
        // Buscamos o creamos el reporte correspondiente a la FECHA de la actividad,
        // no a la fecha actual del sistema.

        // Obtenemos config para poder crear el reporte si no existe
        $config = ThEmployeeConfig::where('user_id', $userId)->first();
        if (!$config) {
            return response()->json(['message' => 'Usuario no configurado.'], 403);
        }

        $targetReport = ThOvertimeReport::firstOrCreate(
            [
                'driver_id' => $userId,
                'month' => $targetMonth,
                'year' => $targetYear,
            ],
            [
                'status' => 'borrador',
                'rmu_at_submission' => $config->rmu,
                'hour_value' => $config->rmu / 240 // Usamos el divisor constante
            ]
        );

        // 4. VALIDACIÓN DE SEGURIDAD CRÍTICA
        // Si el usuario intenta guardar algo en Noviembre, pero el reporte de Noviembre
        // ya fue enviado (no está en borrador), debemos bloquearlo.
        if ($targetReport->status !== 'borrador') {
            return response()->json([
                'message' => "No puedes agregar actividades a {$targetMonth}/{$targetYear} porque ese reporte ya se encuentra en estado: {$targetReport->status}."
            ], 409);
        }

        // 5. Sobreescribimos el ID del reporte en la data con el ID correcto
        $data['overtime_report_id'] = $targetReport->id;

        // --- (El resto de tu lógica de cálculo se mantiene igual) ---
        $tempEntry = new ThOvertimeEntry($data);

        $start = Carbon::parse($data['start_time']);
        $end = Carbon::parse($data['end_time']);

        $duration = $end->diffInMinutes($start);

        if ($start->isWeekday()) {
            $workStart = $start->copy()->setTime(8, 0, 0);
            $workEnd = $start->copy()->setTime(16, 30, 0);

            $overlapStart = $start->max($workStart);
            $overlapEnd = $end->min($workEnd);

            if ($overlapStart < $overlapEnd) {
                $officeMinutes = $overlapEnd->diffInMinutes($overlapStart);
                $duration -= $officeMinutes;
            }
        }

        $data['duration_minutes'] = max(0, $duration);

        // Creamos la entrada vinculada al reporte correcto
        $entry = ThOvertimeEntry::create($data);

        // Opcional: Recalcular solo los "Raw Entries" de ese reporte para tener los datos frescos
        $this->calculationService->calculateRawEntries($targetReport);

        return response()->json($entry, 201);
    }
    /**
     * Actualiza una entrada (viaje)
     * PUT /api/v1/talento-humano/entries/{entry}
     */
    public function updateEntry(UpdateEntryRequest $request, ThOvertimeEntry $entry): JsonResponse
    {
        $data = $request->validated();

        // Recalcular duración si las horas cambiaron
        $start = Carbon::parse($data['start_time'] ?? $entry->start_time);
        $end = Carbon::parse($data['end_time'] ?? $entry->end_time);
        $data['duration_minutes'] = $end->diffInMinutes($start);

        $entry->update($data);

        return response()->json($entry);
    }

    /**
     * Elimina una entrada (viaje)
     * DELETE /api/v1/talento-humano/entries/{entry}
     */
    public function destroyEntry(Request $request, ThOvertimeEntry $entry): JsonResponse
    {
        // 1. Autorización
        if ($entry->report->driver_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Lógica de negocio
        if ($entry->report->status !== 'borrador') {
            return response()->json(['message' => 'El reporte ya fue enviado y no se puede modificar'], 403);
        }

        $entry->delete();

        return response()->json(null, 204); // 204 No Content
    }

    /**
     * Envía el reporte para aprobación
     * POST /api/v1/talento-humano/reports/{report}/submit
     */
    public function submitReport(Request $request, ThOvertimeReport $report): JsonResponse
    {
        // 1. Autorización
        if ($report->driver_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Lógica de negocio
        if ($report->status !== 'borrador') {
            return response()->json(['message' => 'El reporte ya fue enviado'], 409); // 409 Conflict
        }

        // 3. Obtener RMU final
        $config = ThEmployeeConfig::find(Auth::id());

        $this->calculationService->calculate($report);

        // 4. Actualizar estado y guardar
        $report->update([
            'status' => 'pendiente_daf',
            'submitted_at' => now(),
            'rmu_at_submission' => $config->rmu,
            'hour_value' => $config->rmu / self::RMU_HOURS_DIVISOR
        ]);

        // TODO: Disparar evento/notificación para los Supervisores
        // event(new ReportSubmitted($report));

        return response()->json($report);
    }

    /**
     * Devuelve un log de cómo se calcularía un reporte.
     * GET /api/v1/talento-humano/reports/{report}/debug
     */
    public function debugReport(Request $request, ThOvertimeReport $report): JsonResponse
    {
        // 1. Autorización (simplificada)
        if ($report->driver_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Cargar entradas (importante)
        $report->loadMissing('entries');

        // 3. Llamar a la nueva función de depuración del servicio
        $debugLog = $this->calculationService->debugReportLogic($report->entries);

        // 4. Devolver el log como JSON
        return response()->json([
            'report_id' => $report->id,
            'driver_name' => $report->driver->name,
            'calculation_log' => $debugLog
        ]);
    }
}
