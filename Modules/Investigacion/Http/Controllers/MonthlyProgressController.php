<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\ActivityExecutionProgress;
use Exception;

class MonthlyProgressController extends Controller
{
    /**
     * Obtiene las actividades pendientes de reporte para un mes específico.
     * REGLAS:
     * 1. Pertenecen al usuario.
     * 2. NO han sido reportadas aún este mes.
     * 3. TIENEN planificación mayor a 0% para este mes (Nuevo requisito).
     */
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        try {
            $user = $request->user();

            $targetMonth = $request->has('month')
                ? Carbon::parse($request->input('month'))->startOfMonth()
                : Carbon::now()->subMonth()->startOfMonth();

            $reportedActivityIds = ActivityExecutionProgress::where('month', $targetMonth)
                ->pluck('activity_id')
                ->unique();

            $activities = Activity::query()
                ->whereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->whereNotIn('id', $reportedActivityIds)
                ->whereHas('monthlyProgress', function ($query) use ($targetMonth) {
                    $query->where('month', $targetMonth)
                        ->where('percentage', '>', 0);
                })
                ->with(['monthlyProgress' => function ($query) use ($targetMonth) {
                    $query->where('month', $targetMonth);
                }])
                ->get();

            $formattedData = $activities->map(function ($activity) use ($targetMonth) {
                $plannedProgress = $activity->monthlyProgress->first();
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'month_to_report' => $targetMonth->format('Y-m-d'),
                    'planned_percentage' => $plannedProgress ? $plannedProgress->percentage : 0,
                    'budget' => $activity->budget,
                    'accrued_budget' => $activity->accrued_budget,
                ];
            });

            return response()->json(['data' => $formattedData]);

        } catch (Exception $e) {
            Log::error("Error getting monthly activities: " . $e->getMessage());
            return response()->json([
                'msg' => ['summary' => 'Error', 'detail' => 'Error al cargar actividades: ' . $e->getMessage(), 'code' => 500]
            ], 500);
        }
    }

    /**
     * Guarda el avance mensual.
     * AHORA INCLUYE: evidence_url
     */
    public function store(Request $request)
    {
        $request->validate([
            'reports' => ['required', 'array'],
            'reports.*.activity_id' => ['required', 'exists:activities,id'],
            'reports.*.month' => ['required', 'date_format:Y-m-d'],
            'reports.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'reports.*.accrued_budget' => ['required', 'numeric', 'min:0'],
            'reports.*.observation' => ['nullable', 'string'],
            'reports.*.evidence_url' => ['nullable', 'string', 'url'],
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->reports as $report) {
                ActivityExecutionProgress::updateOrCreate(
                    [
                        'activity_id' => $report['activity_id'],
                        'month' => $report['month'],
                    ],
                    [
                        'percentage' => $report['percentage'],
                        'accrued_budget' => $report['accrued_budget'], // Ahora sí existe la columna
                        'observation' => $report['observation'] ?? null,
                        'evidence_url' => $report['evidence_url'] ?? null,
                    ]
                );

                $activity = Activity::find($report['activity_id']);
                if ($activity) {
                    $totalAccrued = ActivityExecutionProgress::where('activity_id', $activity->id)
                        ->sum('accrued_budget');

                    $activity->accrued_budget = $totalAccrued;
                    $activity->save();
                }
            }
            DB::commit();
            return response()->json(['msg' => ['summary' => 'Éxito', 'detail' => 'Progreso actualizado', 'code' => 201]]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => ['summary' => 'Error', 'detail' => $e->getMessage(), 'code' => 500]], 500);
        }
    }

    /**
     * Obtiene el historial de lo que YA se reportó.
     * AHORA INCLUYE: evidence_url en la respuesta
     */
    public function getReported(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        try {
            $user = $request->user();
            $targetMonth = $request->has('month')
                ? Carbon::parse($request->input('month'))->startOfMonth()
                : Carbon::now()->subMonth()->startOfMonth();

            $activities = Activity::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->whereHas('monthlyExecutionProgress', function ($query) use ($targetMonth) {
                    $query->where('month', $targetMonth);
                })
                ->with([
                    'monthlyProgress' => fn($q) => $q->where('month', $targetMonth),
                    'monthlyExecutionProgress' => fn($q) => $q->where('month', $targetMonth)
                ])
                ->get();

            $formattedData = $activities->map(function ($activity) use ($targetMonth) {
                $planned = $activity->monthlyProgress->first();
                $execution = $activity->monthlyExecutionProgress->first();

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'budget' => $activity->budget,
                    'accrued_budget' => $activity->accrued_budget,
                    'month_reported' => $targetMonth->format('Y-m'),
                    'planned_percentage' => $planned ? $planned->percentage : 0,
                    'reported_percentage' => $execution ? $execution->percentage : 0,
                    'reported_observation' => $execution ? $execution->observation : '',
                    'evidence_url' => $execution ? $execution->evidence_url : null, // <--- SE ENVÍA AL FRONT
                ];
            });

            return response()->json(['data' => $formattedData]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
