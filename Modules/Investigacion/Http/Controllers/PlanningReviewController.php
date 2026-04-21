<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Http\Requests\WeekPlanner\UpdateWeekActivityStatusRequest;
use Modules\Investigacion\Notifications\PlannerAccept;
use Modules\Investigacion\Services\PlanningReviewService;
use Modules\Investigacion\Transformers\PlanningReviewResource;

class PlanningReviewController extends Controller
{
    protected $reviewService;

    public function __construct(PlanningReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Obtiene la data aplanada para el Dashboard de Revisión.
     */
    public function index(Request $request)
    {
        try {
            $period = $request->query('period', '15days');

            $activities = $this->reviewService->getWeeklyPlanningData($request->user(), $period);

            return response()->json([
                'msg' => [
                    'summary' => 'Éxito',
                    'detail' => 'Planificaciones cargadas correctamente.',
                    'code' => 200
                ],
                'data' => PlanningReviewResource::collection($activities)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en PlanningReviewController@index: ' . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'No se pudieron cargar las planificaciones.',
                    'code' => 500
                ]
            ], 500);
        }
    }

    /**
     * Aprueba, rechaza o reasigna una actividad semanal.
     */
    public function updateStatus(UpdateWeekActivityStatusRequest $request, $activityId)
    {
        try {
            // La validación de seguridad (authorize) ya pasó exitosamente
            $weekActivity = WeekActivity::findOrFail($activityId);
            $status = $request->validated('status');

            $weekActivity->status = $status;

            if (!$weekActivity->save()) {
                throw new \Exception("No se pudo guardar la actividad en base de datos.");
            }

            $creator = $weekActivity->user;
            $approver = $request->user();

            if ($creator && $approver && $creator->id !== $approver->id) {
                $creator->notify(new PlannerAccept($weekActivity, $approver, $status));
            }

            return response()->json([
                'msg' => [
                    'summary' => 'Estado Actualizado',
                    'detail' => 'Actividad actualizada correctamente.',
                    'code' => 200
                ],
                'data' => [
                    'activity_id' => $activityId,
                    'status' => $status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error en PlanningReviewController@updateStatus: " . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'No se pudo actualizar la actividad.',
                    'code' => 500
                ]
            ], 500);
        }
    }
}
