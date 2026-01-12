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
     * Obtiene el reporte activo para trabajar.
     * CORRECCIÓN: Si el último reporte ya fue enviado, crea automáticamente la siguiente versión.
     */
    public function getCurrentReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Verificar configuración
        $config = ThEmployeeConfig::where('user_id', $user->id)->first();
        if (!$config) {
            return response()->json(['message' => 'Usuario no configurado.'], 403);
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // 1. Buscamos la versión más reciente (sea V1, V2, etc.)
        $latestReport = ThOvertimeReport::where('driver_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('version')
            ->first();

        // LÓGICA DE AUTO-CREACIÓN DE VERSIÓN

        // CASO A: No existe nada -> Crear V1
        if (!$latestReport) {
            $currentReport = ThOvertimeReport::create([
                'driver_id' => $user->id,
                'month' => $month,
                'year' => $year,
                'version' => 1,
                'status' => 'borrador',
                'rmu_at_submission' => $config->rmu,
                'hour_value' => $config->rmu / self::RMU_HOURS_DIVISOR
            ]);
        }
        // CASO B: Existe, pero ya no es borrador (fue enviado/aprobado) -> Crear V2, V3...
        elseif ($latestReport->status !== 'borrador') {

            // ¡AQUÍ ESTÁ LA SOLUCIÓN!
            // En lugar de devolver el reporte cerrado, creamos uno nuevo para que el usuario pueda trabajar.

            $currentReport = ThOvertimeReport::create([
                'driver_id' => $user->id,
                'month' => $month,
                'year' => $year,
                'version' => $latestReport->version + 1, // Siguiente versión
                'status' => 'borrador',
                'rmu_at_submission' => $config->rmu,
                'hour_value' => $config->rmu / self::RMU_HOURS_DIVISOR
            ]);
        }
        // CASO C: Existe y es borrador -> Seguimos usando este
        else {
            $currentReport = $latestReport;
        }

        // Siempre recalculamos para asegurar que la UI muestre totales correctos
        $this->calculationService->calculateRawEntries($currentReport);

        // Cargamos relaciones
        $currentReport->load('entries.activityType', 'entries.vehicle');

        return response()->json($currentReport);
    }

    /**
     * Obtiene TODOS los reportes (historial) de un mes específico.
     * GET /api/v1/talento-humano/history?month=12&year=2025
     */
    public function getMonthHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $reports = ThOvertimeReport::where('driver_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('version', 'asc') // Ordenar 1, 2, 3...
            ->with(['entries']) // Cargar entradas si quieres mostrarlas en el listado
            ->get();

        return response()->json($reports);
    }

    /**
     * Almacena una nueva entrada.
     * LÓGICA CLAVE: Si el reporte actual está cerrado, crea una V2 (o V3) automáticamente.
     */
    public function storeEntry(StoreEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $entryDate = Carbon::parse($data['date']);
        $targetMonth = $entryDate->month;
        $targetYear = $entryDate->year;
        $userId = Auth::id();

        $config = ThEmployeeConfig::where('user_id', $userId)->first();
        if (!$config) {
            return response()->json(['message' => 'Usuario no configurado.'], 403);
        }

        // --- INICIO LÓGICA DE VERSIONADO ---

        // 1. Buscamos si ya existe un borrador ACTIVO para este mes (V1, V2, etc.)
        // Si ya hay un borrador abierto, queremos agregar la entrada ahí.
        $activeDraft = ThOvertimeReport::where('driver_id', $userId)
            ->where('month', $targetMonth)
            ->where('year', $targetYear)
            ->where('status', 'borrador')
            ->first();

        if ($activeDraft) {
            $targetReport = $activeDraft;
        } else {
            // 2. Si NO hay borrador activo, significa que el reporte anterior (si existe) ya se envió.
            // Buscamos cuál fue la última versión registrada.
            $lastVersionReport = ThOvertimeReport::where('driver_id', $userId)
                ->where('month', $targetMonth)
                ->where('year', $targetYear)
                ->orderByDesc('version')
                ->first();

            // Si existe reporte previo, la nueva versión es +1. Si no, es 1.
            $nextVersion = $lastVersionReport ? ($lastVersionReport->version + 1) : 1;

            // Creamos el nuevo reporte (ej: V2) en estado borrador
            $targetReport = ThOvertimeReport::create([
                'driver_id' => $userId,
                'month' => $targetMonth,
                'year' => $targetYear,
                'version' => $nextVersion,
                'status' => 'borrador',
                'rmu_at_submission' => $config->rmu,
                'hour_value' => $config->rmu / self::RMU_HOURS_DIVISOR
            ]);
        }

        // Asignamos el ID del reporte correcto (sea el borrador existente o la nueva V2)
        $data['overtime_report_id'] = $targetReport->id;

        // Cálculos de duración y oficina (Sin cambios)
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

        // Crear la entrada
        $entry = ThOvertimeEntry::create($data);

        // Recalcular los valores raw del reporte actual
        $this->calculationService->calculateRawEntries($targetReport);

        // Devolvemos la entrada cargando el reporte, para que el Frontend sepa si ahora está en V2
        return response()->json($entry->load('report'), 201);
    }

    /**
     * Ver un reporte específico por ID (útil para ver versiones viejas).
     * GET /api/v1/talento-humano/report/{id}
     */
    public function show($id): JsonResponse
    {
        $report = ThOvertimeReport::with(['entries.activityType', 'entries.vehicle'])
            ->where('driver_id', Auth::id())
            ->findOrFail($id);

        return response()->json($report);
    }

    /**
     * Update: Solo permitido si el reporte padre está en borrador.
     */
    public function updateEntry(UpdateEntryRequest $request, ThOvertimeEntry $entry): JsonResponse
    {
        // Validación de seguridad: El reporte padre debe ser borrador
        if ($entry->report->status !== 'borrador') {
            return response()->json(['message' => 'Este reporte ya fue enviado. No se puede editar.'], 403);
        }

        $data = $request->validated();

        $start = Carbon::parse($data['start_time'] ?? $entry->start_time);
        $end = Carbon::parse($data['end_time'] ?? $entry->end_time);

        // Recalculo simple de duración
        $data['duration_minutes'] = $end->diffInMinutes($start);

        // NOTA: Si necesitas recalcular descuento de oficina aquí también,
        // deberías extraer la lógica de storeEntry a un método privado reusable.

        $entry->update($data);

        // Recalculamos para mantener los S/E actualizados en UI
        $this->calculationService->calculateRawEntries($entry->report);

        return response()->json($entry);
    }

    /**
     * Destroy: Solo permitido si el reporte padre está en borrador.
     */
    public function destroyEntry(Request $request, ThOvertimeEntry $entry): JsonResponse
    {
        if ($entry->report->driver_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($entry->report->status !== 'borrador') {
            return response()->json(['message' => 'El reporte ya fue enviado y no se puede modificar'], 403);
        }

        $report = $entry->report;
        $entry->delete();

        // Recalcular tras borrar
        $this->calculationService->calculateRawEntries($report);

        return response()->json(null, 204);
    }

    /**
     * Submit: Envía la versión actual (V1, V2, etc)
     */
    public function submitReport(Request $request, ThOvertimeReport $report): JsonResponse
    {
        if ($report->driver_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($report->status !== 'borrador') {
            return response()->json(['message' => 'El reporte ya fue enviado'], 409);
        }

        $config = ThEmployeeConfig::find(Auth::id());

        // Calculamos totales finales (USD, Límites, etc)
        $this->calculationService->calculate($report);

        $report->update([
            'status' => 'pendiente_daf',
            'submitted_at' => now(),
            'rmu_at_submission' => $config->rmu,
            'hour_value' => $config->rmu / self::RMU_HOURS_DIVISOR
        ]);

        return response()->json($report);
    }
}
