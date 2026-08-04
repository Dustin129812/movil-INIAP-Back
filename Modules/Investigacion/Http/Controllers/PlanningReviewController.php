<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Modules\Investigacion\Http\Requests\WeekPlanner\GetPlanningReviewsRequest;
use Modules\Investigacion\Http\Requests\WeekPlanner\UpdateWeekActivityStatusRequest;
use Modules\Investigacion\Services\PlanningReviewService;
use Modules\Investigacion\Transformers\PlanningReviewResource;

class PlanningReviewController extends Controller
{
    protected $reviewService;

    public function __construct(PlanningReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function index(GetPlanningReviewsRequest $request)
    {
        try {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $activities = $this->reviewService->getWeeklyPlanningDataByRange(
                    $request->user(),
                    $request->validated('start_date'),
                    $request->validated('end_date')
                );
            } else {
                $period = $request->validated('period') ?? '15days';
                $activities = $this->reviewService->getWeeklyPlanningData($request->user(), $period);
            }

            return response()->json([
                'msg' => ['summary' => 'Éxito', 'detail' => 'Planificaciones cargadas correctamente.', 'code' => 200],
                'data' => PlanningReviewResource::collection($activities)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en PlanningReviewController@index: ' . $e->getMessage());
            return response()->json(['msg' => ['summary' => 'Error', 'detail' => 'No se pudieron cargar las planificaciones.', 'code' => 500]], 500);
        }
    }

    public function updateStatus(UpdateWeekActivityStatusRequest $request, $activityId)
    {
        try {
            $data = $this->reviewService->updateActivityStatus(
                $activityId,
                $request->validated('status'),
                $request->user()
            );

            return response()->json([
                'msg' => [
                    'summary' => 'Estado Actualizado',
                    'detail' => 'Actividad actualizada correctamente.',
                    'code' => 200
                ],
                'data' => $data
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

    public function downloadEvidence(Request $request)
    {
        $path = $request->query('path');
        $disk = Storage::disk('verificables_externos');

        if (!$path || !$disk->exists($path)) {
            abort(404, 'El documento verificable no se encuentra en el servidor.');
        }

        return response()->file($disk->path($path));
    }

    public function prepareUserZip(GetPlanningReviewsRequest $request, $userId)
    {
        try {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $zipFileName = $this->reviewService->generateUserEvidenceZipByRange(
                    $userId,
                    $request->validated('start_date'),
                    $request->validated('end_date')
                );
            } else {
                $period = $request->validated('period') ?? '15days';
                $zipFileName = $this->reviewService->generateUserEvidenceZip($userId, $period);
            }

            $url = URL::temporarySignedRoute('api.investigacion.evidence.zip.download', now()->addMinutes(15), ['filename' => $zipFileName]);
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('Error en prepareUserZip: ' . $e->getMessage());
            return response()->json(['msg' => ['detail' => $e->getMessage()]], 500);
        }
    }

    public function prepareAllUsersZip(GetPlanningReviewsRequest $request)
    {
        try {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $zipFileName = $this->reviewService->generateAllUsersEvidenceZipByRange(
                    $request->user(),
                    $request->validated('start_date'),
                    $request->validated('end_date')
                );
            } else {
                $period = $request->validated('period') ?? '15days';
                $zipFileName = $this->reviewService->generateAllUsersEvidenceZip($request->user(), $period);
            }

            $url = URL::temporarySignedRoute('api.investigacion.evidence.zip.download', now()->addMinutes(30), ['filename' => $zipFileName]);

            return response()->json([
                'msg' => ['summary' => 'Archivo generado', 'detail' => 'El compilado global está listo para descargar.', 'code' => 200],
                'url' => $url
            ]);

        } catch (\Exception $e) {
            Log::error('Error en prepareAllUsersZip: ' . $e->getMessage());
            return response()->json(['msg' => ['summary' => 'Error', 'detail' => $e->getMessage(), 'code' => 500]], 500);
        }
    }

    public function downloadZip(Request $request)
    {
        $filename = $request->query('filename');
        $disk = Storage::disk('verificables_externos');
        $relativeTemporalPath = 'temp_zips/' . $filename;

        if (!$filename || !$disk->exists($relativeTemporalPath)) {
            abort(404, 'El archivo solicitado no existe o el enlace ha expirado.');
        }

        $absolutePath = $disk->path($relativeTemporalPath);

        return response()->download($absolutePath)->deleteFileAfterSend(true);
    }
}
