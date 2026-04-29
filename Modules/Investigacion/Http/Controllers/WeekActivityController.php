<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Http\Requests\WeekPlanner\UpdateWeeklyActivityRequest;
use Modules\Investigacion\Services\WeekActivityService;
use Modules\Investigacion\Transformers\WeekActivityResource;
use Modules\Investigacion\Http\Requests\WeekPlanner\StoreWeeklyPlanRequest;
use Modules\Investigacion\Http\Requests\WeekPlanner\UpdateProgressRequest;
use Modules\Investigacion\Http\Requests\WeekPlanner\RespondSupportRequest;

class WeekActivityController extends Controller
{
    protected $weekActivityService;

    public function __construct(WeekActivityService $weekActivityService)
    {
        $this->weekActivityService = $weekActivityService;
    }

    public function weeklyPlanner(StoreWeeklyPlanRequest $request)
    {
        try {
            $this->weekActivityService->saveWeeklyPlan(
                $request->validated('weeklyPlans'),
                $request->user()
            );

            return response()->json(['message' => 'Planificación guardada correctamente.']);
        } catch (\Exception $e) {
            Log::error("Error en weeklyPlanner: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registerPastWeek(StoreWeeklyPlanRequest $request)
    {
        try {
            $baseMonday = Carbon::parse($request->validated('selected_date'))->startOfWeek(Carbon::MONDAY);

            $this->weekActivityService->saveWeeklyPlan(
                $request->validated('weeklyPlans'),
                $request->user(),
                $baseMonday
            );

            return response()->json(['message' => 'Planificación de semana pasada guardada correctamente.']);
        } catch (\Exception $e) {
            Log::error("Error en registerPastWeek: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPreviousWeekActivities(Request $request)
    {
        try {
            $activities = $this->weekActivityService->getUserActivities(
                $request->user(),
                $request->query('offset') !== null ? (int)$request->query('offset') : null
            );

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Actividades obtenidas correctamente',
                    'code' => 200,
                ],
                'data' => WeekActivityResource::collection($activities)
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener actividades: " . $e->getMessage());
            return response()->json([
                'msg' => ['summary' => 'Error', 'detail' => $e->getMessage(), 'code' => 500]
            ], 500);
        }
    }

    public function updateActivity(UpdateWeeklyActivityRequest $request, $id)
    {
        try {
            $activity = $this->weekActivityService->updateActivity(
                $id,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Actividad actualizada correctamente.',
                    'code' => 200
                ],
                'data' => new WeekActivityResource($activity)
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar actividad: " . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => $e->getMessage(),
                    'code' => 500
                ]
            ], 500);
        }
    }

    public function updateWeeklyProgress(UpdateProgressRequest $request)
    {
        try {
            $this->weekActivityService->updateProgress($request->validated('progress'), $request->user());

            return response()->json([
                'msg' => ['summary' => 'Success', 'detail' => 'Progreso actualizado', 'code' => 200]
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando progreso: " . $e->getMessage());
            return response()->json([
                'msg' => ['summary' => 'Error', 'detail' => $e->getMessage(), 'code' => 500]
            ], 500);
        }
    }

    public function respondToSupport(RespondSupportRequest $request, $activityId)
    {
        try {
            $this->weekActivityService->respondToSupportRequest(
                $activityId,
                $request->user(),
                $request->validated('status')
            );

            return response()->json([
                'message' => 'Respuesta registrada.',
                'new_support_status' => $request->validated('status')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
