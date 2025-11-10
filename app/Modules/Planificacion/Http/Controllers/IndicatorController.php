<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Http\Requests\Requests\StoreIndicatorRequest;
use App\Modules\Planificacion\Http\Requests\Requests\UpdateIndicatorRequest;
use App\Modules\Planificacion\Models\Performance_Indicator;
use Illuminate\Http\JsonResponse;

class IndicatorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Performance_Indicator::latest()->paginate(15));
    }

    public function store(StoreIndicatorRequest $request): JsonResponse
    {
        $indicator = Performance_Indicator::create($request->validated());
        return response()->json($indicator, 201);
    }

    public function show(Performance_Indicator $indicator): JsonResponse
    {
        return response()->json($indicator);
    }

    public function update(UpdateIndicatorRequest $request, Performance_Indicator $indicator): JsonResponse
    {
        $indicator->update($request->validated());
        return response()->json($indicator);
    }

    public function destroy(Performance_Indicator $indicator): JsonResponse
    {
        $indicator->delete();
        return response()->json(null, 204);
    }
}
