<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Investigacion\Entities\ReusableActivity;
use Modules\Investigacion\Http\Requests\WeekPlanner\StoreReusableActivityRequest;
use Modules\Investigacion\Http\Requests\WeekPlanner\UpdateReusableActivityRequest;
use Modules\Investigacion\Services\ReusableActivityService;
use Modules\Investigacion\Transformers\ReusableActivityResource;

class ReusableActivityController extends Controller
{
    public function __construct(
        private readonly ReusableActivityService $activityService
    ) {}

    public function index(): JsonResponse
    {
        $reusableActivities = ReusableActivity::with([
            'activity.product',
            'materials',
            'performanceIndicators',
            'logisticSupportUsers'
        ])
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ReusableActivityResource::collection($reusableActivities)
        ]);
    }

    public function store(StoreReusableActivityRequest $request): JsonResponse
    {
        $reusable = $this->activityService->store($request->validated(), Auth::id());

        return response()->json(new ReusableActivityResource($reusable), 201);
    }

    public function update(UpdateReusableActivityRequest $request, ReusableActivity $reusableActivity): JsonResponse
    {
        if ($reusableActivity->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $reusable = $this->activityService->update($reusableActivity, $request->validated());

        return response()->json(new ReusableActivityResource($reusable), 200);
    }

    public function destroy(ReusableActivity $reusableActivity): JsonResponse
    {
        if ($reusableActivity->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $this->activityService->destroy($reusableActivity);

        return response()->json(null, 204);
    }
}
